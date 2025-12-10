<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Create a new payment for an appointment
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $validated = $request->validate([
            'appointment_id' => 'required|exists:appointments,appointment_id',
            'amount' => 'required|numeric|min:0',
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:Cash,Later,Partial,Exempt',
            'notes' => 'nullable|string|max:500',
            'exemption_reason' => 'nullable|string|max:255',
        ]);

        // Get the appointment and verify it belongs to this clinic
        $appointment = Appointment::where('appointment_id', $validated['appointment_id'])
            ->where('clinic_id', $user->clinic_id)
            ->firstOrFail();

        // Check if payment already exists for this appointment
        $existingPayment = Payment::where('appointment_id', $appointment->appointment_id)->first();
        if ($existingPayment) {
            return response()->json([
                'message' => 'Payment already exists for this appointment',
                'payment' => $existingPayment,
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Determine payment status based on method
            $status = match ($validated['payment_method']) {
                'Cash' => 'Paid',
                'Exempt' => 'Exempt',
                'Later' => 'Pending',
                'Partial' => 'Partial',
                default => 'Pending',
            };

            $amountPaid = $validated['amount_paid'] ?? 0;

            // For cash payments, amount paid equals full amount
            if ($validated['payment_method'] === 'Cash') {
                $amountPaid = $validated['amount'];
            }

            // For exempt, no payment needed
            if ($validated['payment_method'] === 'Exempt') {
                $amountPaid = 0;
            }

            // Create the payment
            $payment = Payment::create([
                'appointment_id' => $appointment->appointment_id,
                'patient_id' => $appointment->patient_id,
                'clinic_id' => $user->clinic_id,
                'received_by' => $user->user_id,
                'amount' => $validated['amount'],
                'amount_paid' => $amountPaid,
                'payment_method' => $validated['payment_method'],
                'status' => $status,
                'payment_date' => in_array($status, ['Paid', 'Partial']) ? now() : null,
                'notes' => $validated['notes'] ?? null,
                'exemption_reason' => $validated['exemption_reason'] ?? null,
            ]);

            // Update appointment payment status
            $appointment->update([
                'fee_amount' => $validated['amount'],
                'payment_status' => $status,
            ]);

            DB::commit();

            // Load relationships for response
            $payment->load(['patient.user', 'receiver']);

            return response()->json([
                'message' => 'Payment recorded successfully',
                'payment' => $payment,
                'receipt_number' => $payment->receipt_number,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to record payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get payment details by ID
     */
    public function show(Request $request, $payment_id)
    {
        $user = $request->user();

        $payment = Payment::where('payment_id', $payment_id)
            ->where('clinic_id', $user->clinic_id)
            ->with(['patient.user', 'appointment.doctor.user', 'receiver'])
            ->firstOrFail();

        return response()->json([
            'payment' => $payment,
        ], 200);
    }

    /**
     * Get all payments for the clinic
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $query = Payment::where('clinic_id', $user->clinic_id)
            ->with(['patient.user', 'appointment.doctor.user', 'receiver']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('payment_date', [$request->start_date, $request->end_date]);
        }

        // Filter by today
        if ($request->has('today') && $request->today === 'true') {
            $query->whereDate('payment_date', today());
        }

        // Filter by patient
        if ($request->has('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($payments, 200);
    }

    /**
     * Get pending payments for the clinic
     */
    public function pendingPayments(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $pendingPayments = Payment::where('clinic_id', $user->clinic_id)
            ->whereIn('status', ['Pending', 'Partial'])
            ->with(['patient.user', 'appointment.doctor.user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $totalPending = $pendingPayments->sum(function ($payment) {
            return $payment->amount - $payment->amount_paid;
        });

        return response()->json([
            'payments' => $pendingPayments,
            'total_pending' => $totalPending,
            'count' => $pendingPayments->count(),
        ], 200);
    }

    /**
     * Get daily payment report
     */
    public function dailyReport(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $date = $request->get('date', today()->format('Y-m-d'));

        $payments = Payment::where('clinic_id', $user->clinic_id)
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

        // Group by receiver (who collected)
        $byReceiver = $payments->groupBy('received_by')->map(function ($group) {
            return [
                'receiver_name' => $group->first()->receiver?->name ?? 'Unknown',
                'total_collected' => $group->sum('amount_paid'),
                'transaction_count' => $group->count(),
            ];
        })->values();

        return response()->json([
            'summary' => $summary,
            'by_receiver' => $byReceiver,
            'payments' => $payments,
        ], 200);
    }

    /**
     * Update payment (e.g., mark pending as paid)
     */
    public function update(Request $request, $payment_id)
    {
        $user = $request->user();

        $payment = Payment::where('payment_id', $payment_id)
            ->where('clinic_id', $user->clinic_id)
            ->firstOrFail();

        $validated = $request->validate([
            'amount_paid' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:Cash,Later,Partial,Exempt',
            'status' => 'nullable|in:Paid,Pending,Partial,Exempt,Refunded',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            // Update amount paid if provided
            if (isset($validated['amount_paid'])) {
                $payment->amount_paid = $validated['amount_paid'];
                
                // Auto-update status based on amount
                if ($payment->amount_paid >= $payment->amount) {
                    $payment->status = 'Paid';
                    $payment->payment_date = now();
                } elseif ($payment->amount_paid > 0) {
                    $payment->status = 'Partial';
                    $payment->payment_date = now();
                }
            }

            // Update other fields if provided
            if (isset($validated['status'])) {
                $payment->status = $validated['status'];
                if (in_array($validated['status'], ['Paid', 'Partial']) && !$payment->payment_date) {
                    $payment->payment_date = now();
                }
            }

            if (isset($validated['payment_method'])) {
                $payment->payment_method = $validated['payment_method'];
            }

            if (isset($validated['notes'])) {
                $payment->notes = $validated['notes'];
            }

            $payment->save();

            // Update appointment payment status
            $payment->appointment->update([
                'payment_status' => $payment->status,
            ]);

            DB::commit();

            $payment->load(['patient.user', 'receiver']);

            return response()->json([
                'message' => 'Payment updated successfully',
                'payment' => $payment,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to update payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get patient payment history
     */
    public function patientHistory(Request $request, $patient_id)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        // Verify patient belongs to clinic
        $patient = Patient::where('patient_id', $patient_id)
            ->whereHas('user', function ($q) use ($user) {
                $q->where('clinic_id', $user->clinic_id);
            })
            ->firstOrFail();

        $payments = Payment::where('patient_id', $patient_id)
            ->where('clinic_id', $user->clinic_id)
            ->with(['appointment.doctor.user', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = [
            'total_paid' => $payments->where('status', 'Paid')->sum('amount_paid'),
            'total_pending' => $payments->whereIn('status', ['Pending', 'Partial'])->sum(function ($p) {
                return $p->amount - $p->amount_paid;
            }),
            'total_exempt' => $payments->where('status', 'Exempt')->count(),
            'total_visits' => $payments->count(),
        ];

        return response()->json([
            'patient' => [
                'patient_id' => $patient->patient_id,
                'name' => $patient->user->name,
            ],
            'summary' => $summary,
            'payments' => $payments,
        ], 200);
    }
}
