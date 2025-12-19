<?php

namespace App\Services\Clinic;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * Get doctors that belong to the authenticated user's clinic.
     */
    public function getAvailableDoctorsForClinic(int $clinicId): Collection
    {
        return Doctor::with('user')
            ->whereHas('user', static function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId);
            })
            ->get();
    }

    /**
     * Create an appointment for a patient and optionally attach a payment.
     *
     * @return array{appointment: Appointment, payment: Payment|null}
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function createForPatient(User $user, array $data): array
    {
        return $this->database->transaction(function () use ($user, $data) {
            $doctor = Doctor::with('user')->findOrFail($data['doctor_id']);

            if (!$doctor->user || $doctor->user->clinic_id !== $user->clinic_id) {
                throw new AuthorizationException('Doctor does not belong to your clinic.');
            }

            $this->ensureTimeSlotIsAvailable($data['doctor_id'], $data['date'], $data['time']);

            $paymentStatus = $this->determinePaymentStatus($data['payment'] ?? null);

            $appointment = Appointment::create([
                'clinic_id' => $user->clinic_id,
                'doctor_id' => $data['doctor_id'],
                'patient_id' => $data['patient_id'],
                'secretary_id' => $user->user_id,
                'appointment_date' => $data['date'],
                'appointment_time' => $data['time'],
                'status' => 'Approved',
                'notes' => $data['notes'] ?? null,
                'fee_amount' => $data['payment']['amount'] ?? $doctor->consultation_fee ?? 0,
                'payment_status' => $paymentStatus,
            ]);

            $payment = null;

            if (!empty($data['payment'])) {
                $payment = $this->createPayment($user, $appointment, $data['payment'], $paymentStatus);
            }

            $appointment->load(['doctor.user', 'patient.user', 'clinic', 'payment']);

            return [
                'appointment' => $appointment,
                'payment' => $payment,
            ];
        });
    }

    /**
     * Get available slots for the doctor after verifying clinic ownership.
     *
     * @throws AuthorizationException
     */
    public function getAvailableTimeSlots(User $user, int $doctorId, string $date): array
    {
        $doctor = Doctor::with('user')->findOrFail($doctorId);

        if (!$doctor->user || $doctor->user->clinic_id !== $user->clinic_id) {
            throw new AuthorizationException('Unauthorized access to doctor.');
        }

        return [
            'date' => $date,
            'doctor' => $doctor,
            'available_slots' => $doctor->getAvailableSlots($date),
        ];
    }

    /**
     * Return patient appointment history scoped to clinic.
     */
    public function getPatientHistory(User $user, int $patientId): Collection
    {
        return Appointment::with(['doctor.user', 'patient.user', 'clinic', 'payment'])
            ->where('patient_id', $patientId)
            ->where('clinic_id', $user->clinic_id)
            ->get();
    }

    /**
     * Ensure the slot is free; throw a validation exception if not.
     *
     * @throws ValidationException
     */
    private function ensureTimeSlotIsAvailable(int $doctorId, string $date, string $time): void
    {
        $existingAppointment = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $date)
            ->where('appointment_time', $time)
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->first();

        if ($existingAppointment) {
            throw ValidationException::withMessages([
                'time' => ['This time slot is already booked. Please choose another time.'],
            ]);
        }
    }

    private function determinePaymentStatus(?array $paymentData): string
    {
        if (empty($paymentData)) {
            return 'Pending';
        }

        return match ($paymentData['payment_method']) {
            'Cash' => 'Paid',
            'Exempt' => 'Exempt',
            'Later' => 'Pending',
            'Partial' => 'Partial',
            default => 'Pending',
        };
    }

    private function createPayment(User $user, Appointment $appointment, array $paymentData, string $paymentStatus): Payment
    {
        $amountPaid = $paymentData['amount_paid'] ?? 0;

        if ($paymentData['payment_method'] === 'Cash') {
            $amountPaid = $paymentData['amount'];
        }

        if ($paymentData['payment_method'] === 'Exempt') {
            $amountPaid = 0;
        }

        return Payment::create([
            'appointment_id' => $appointment->appointment_id,
            'patient_id' => $paymentData['patient_id'] ?? $appointment->patient_id,
            'clinic_id' => $user->clinic_id,
            'received_by' => $user->user_id,
            'amount' => $paymentData['amount'],
            'amount_paid' => $amountPaid,
            'payment_method' => $paymentData['payment_method'],
            'status' => $paymentStatus,
            'payment_date' => in_array($paymentStatus, ['Paid', 'Partial'], true) ? now() : null,
            'notes' => $paymentData['notes'] ?? null,
            'exemption_reason' => $paymentData['exemption_reason'] ?? null,
        ]);
    }
}
