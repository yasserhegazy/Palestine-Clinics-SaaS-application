<?php

namespace App\Services\Clinic;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * @return Payment
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(User $actor, array $data): Payment
    {
        $this->ensureActorHasClinic($actor);

        $appointment = Appointment::where('appointment_id', $data['appointment_id'])
            ->where('clinic_id', $actor->clinic_id)
            ->firstOrFail();

        if (Payment::where('appointment_id', $appointment->appointment_id)->exists()) {
            throw ValidationException::withMessages([
                'appointment_id' => ['Payment already exists for this appointment.'],
            ]);
        }

        $status = $this->paymentStatusFromMethod($data['payment_method'], $data['amount_paid'] ?? null, $data['amount']);
        $amountPaid = $this->normalizeAmountPaid($data['payment_method'], $data['amount'], $data['amount_paid'] ?? 0);

        $payment = $this->database->transaction(function () use ($actor, $data, $appointment, $status, $amountPaid) {
            /** @var Payment $payment */
            $payment = Payment::create([
                'appointment_id' => $appointment->appointment_id,
                'patient_id' => $appointment->patient_id,
                'clinic_id' => $actor->clinic_id,
                'received_by' => $actor->user_id,
                'amount' => $data['amount'],
                'amount_paid' => $amountPaid,
                'payment_method' => $data['payment_method'],
                'status' => $status,
                'payment_date' => in_array($status, ['Paid', 'Partial'], true) ? now() : null,
                'notes' => $data['notes'] ?? null,
                'exemption_reason' => $data['exemption_reason'] ?? null,
            ]);

            $appointment->update([
                'fee_amount' => $data['amount'],
                'payment_status' => $status,
            ]);

            return $payment;
        });

        return $payment->fresh(['patient.user', 'appointment.doctor.user', 'receiver']);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(User $actor, int $paymentId): Payment
    {
        $this->ensureActorHasClinic($actor);

        return Payment::where('payment_id', $paymentId)
            ->where('clinic_id', $actor->clinic_id)
            ->with(['patient.user', 'appointment.doctor.user', 'receiver'])
            ->firstOrFail();
    }

    /**
     * @throws AuthorizationException
     */
    public function list(User $actor, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $this->ensureActorHasClinic($actor);

        $query = Payment::where('clinic_id', $actor->clinic_id)
            ->with(['patient.user', 'appointment.doctor.user', 'receiver']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('payment_date', [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['today'])) {
            $query->whereDate('payment_date', today());
        }

        if (!empty($filters['patient_id'])) {
            $query->where('patient_id', $filters['patient_id']);
        }

        $query->orderByDesc('created_at');

        return $query->paginate($perPage);
    }

    /**
     * @return array{payments: Collection<int, Payment>, total_pending: float, count: int}
     *
     * @throws AuthorizationException
     */
    public function pending(User $actor): array
    {
        $this->ensureActorHasClinic($actor);

        $payments = Payment::where('clinic_id', $actor->clinic_id)
            ->whereIn('status', ['Pending', 'Partial'])
            ->with(['patient.user', 'appointment.doctor.user'])
            ->orderBy('created_at')
            ->get();

        $totalPending = $payments->sum(static fn (Payment $payment) => max(0, $payment->amount - $payment->amount_paid));

        return [
            'payments' => $payments,
            'total_pending' => $totalPending,
            'count' => $payments->count(),
        ];
    }

    /**
     * @return array{summary: array<string, mixed>, by_receiver: Collection<int, array<string, mixed>>, payments: Collection<int, Payment>}
     *
     * @throws AuthorizationException
     */
    public function dailyReport(User $actor, string $date): array
    {
        $this->ensureActorHasClinic($actor);

        $payments = Payment::where('clinic_id', $actor->clinic_id)
            ->whereDate('payment_date', $date)
            ->with(['patient.user', 'appointment.doctor.user', 'receiver'])
            ->get();

        $summary = [
            'date' => $date,
            'total_collected' => $payments->where('status', 'Paid')->sum('amount_paid'),
            'total_partial' => $payments->where('status', 'Partial')->sum('amount_paid'),
            'total_exempt' => $payments->where('status', 'Exempt')->count(),
            'total_pending' => $payments->where('status', 'Pending')->sum('amount'),
            'cash_count' => $payments->where('payment_method', 'Cash')->count(),
            'exempt_count' => $payments->where('payment_method', 'Exempt')->count(),
            'total_transactions' => $payments->count(),
        ];

        $byReceiver = $payments->groupBy('received_by')->map(static function (Collection $group) {
            return [
                'receiver_name' => $group->first()->receiver?->name ?? 'Unknown',
                'total_collected' => $group->sum('amount_paid'),
                'transaction_count' => $group->count(),
            ];
        })->values();

        return [
            'summary' => $summary,
            'by_receiver' => $byReceiver,
            'payments' => $payments,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    public function update(User $actor, int $paymentId, array $data): Payment
    {
        $payment = $this->show($actor, $paymentId);

        return $this->database->transaction(function () use ($payment, $data) {
            if (array_key_exists('amount_paid', $data)) {
                $payment->amount_paid = $data['amount_paid'];

                if ($payment->amount_paid >= $payment->amount) {
                    $payment->status = 'Paid';
                    $payment->payment_date = now();
                } elseif ($payment->amount_paid > 0) {
                    $payment->status = 'Partial';
                    $payment->payment_date = now();
                }
            }

            if (!empty($data['status'])) {
                $payment->status = $data['status'];
                if (in_array($payment->status, ['Paid', 'Partial'], true) && !$payment->payment_date) {
                    $payment->payment_date = now();
                }
            }

            if (!empty($data['payment_method'])) {
                $payment->payment_method = $data['payment_method'];
            }

            if (array_key_exists('notes', $data)) {
                $payment->notes = $data['notes'];
            }

            $payment->save();

            $payment->appointment->update([
                'payment_status' => $payment->status,
            ]);

            return $payment->fresh(['patient.user', 'appointment.doctor.user', 'receiver']);
        });
    }

    /**
     * @return array{patient: Patient, summary: array<string, mixed>, payments: Collection<int, Payment>}
     *
     * @throws AuthorizationException
     */
    public function patientHistory(User $actor, int $patientId): array
    {
        $this->ensureActorHasClinic($actor);

        $patient = Patient::where('patient_id', $patientId)
            ->whereHas('user', function ($q) use ($actor) {
                $q->where('clinic_id', $actor->clinic_id);
            })
            ->with('user')
            ->firstOrFail();

        $payments = Payment::where('patient_id', $patientId)
            ->where('clinic_id', $actor->clinic_id)
            ->with(['appointment.doctor.user', 'receiver'])
            ->orderByDesc('created_at')
            ->get();

        $summary = [
            'total_paid' => $payments->where('status', 'Paid')->sum('amount_paid'),
            'total_pending' => $payments->whereIn('status', ['Pending', 'Partial'])->sum(static fn ($p) => $p->amount - $p->amount_paid),
            'total_exempt' => $payments->where('status', 'Exempt')->count(),
            'total_visits' => $payments->count(),
        ];

        return [
            'patient' => $patient,
            'summary' => $summary,
            'payments' => $payments,
        ];
    }

    private function ensureActorHasClinic(User $actor): void
    {
        if (!$actor->clinic_id) {
            throw new AuthorizationException('You are not associated with any clinic.');
        }
    }

    private function paymentStatusFromMethod(string $method, ?float $amountPaid, float $amount): string
    {
        return match ($method) {
            'Cash' => 'Paid',
            'Exempt' => 'Exempt',
            'Later' => 'Pending',
            'Partial' => ($amountPaid ?? 0) >= $amount ? 'Paid' : 'Partial',
            default => 'Pending',
        };
    }

    private function normalizeAmountPaid(string $method, float $amount, float $amountPaid): float
    {
        if ($method === 'Cash') {
            return $amount;
        }

        if ($method === 'Exempt') {
            return 0;
        }

        return $amountPaid;
    }
}
