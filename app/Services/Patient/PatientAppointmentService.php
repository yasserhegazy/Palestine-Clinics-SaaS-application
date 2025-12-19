<?php

namespace App\Services\Patient;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PatientAppointmentService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function create(User $actor, array $data): Appointment
    {
        $patient = $this->resolvePatient($actor);

        $doctor = Doctor::with('user')->findOrFail($data['doctor_id']);
        if ($doctor->user->clinic_id !== $actor->clinic_id) {
            throw new AuthorizationException('Doctor does not belong to your clinic.');
        }

        $this->assertSlotAvailable($data['doctor_id'], $data['appointment_date']);

        return $this->database->transaction(function () use ($actor, $patient, $data) {
            $appointment = Appointment::create([
                'clinic_id' => $actor->clinic_id,
                'doctor_id' => $data['doctor_id'],
                'patient_id' => $patient->patient_id,
                'secretary_id' => null,
                'appointment_date' => $data['appointment_date'],
                'status' => 'Requested',
                'notes' => $data['notes'] ?? null,
            ]);

            return $appointment->fresh(['doctor.user', 'patient.user', 'clinic']);
        });
    }

    /**
     * @return Collection<int, Appointment>
     *
     * @throws AuthorizationException
     */
    public function list(User $actor): Collection
    {
        $patient = $this->resolvePatient($actor);

        return Appointment::where('patient_id', $patient->patient_id)
            ->with(['doctor.user', 'clinic'])
            ->orderByDesc('appointment_date')
            ->get();
    }

    /**
     * @return Collection<int, Appointment>
     *
     * @throws AuthorizationException
     */
    public function upcoming(User $actor): Collection
    {
        $patient = $this->resolvePatient($actor);

        return Appointment::where('patient_id', $patient->patient_id)
            ->where('appointment_date', '>', now())
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->with(['doctor.user', 'clinic'])
            ->orderBy('appointment_date')
            ->get();
    }

    /**
     * @throws AuthorizationException
     */
    public function show(User $actor, int $appointmentId): Appointment
    {
        $patient = $this->resolvePatient($actor);

        return Appointment::where('appointment_id', $appointmentId)
            ->where('patient_id', $patient->patient_id)
            ->with(['doctor.user', 'clinic', 'secretary'])
            ->firstOrFail();
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(User $actor, int $appointmentId, array $data): Appointment
    {
        $patient = $this->resolvePatient($actor);

        $appointment = Appointment::where('appointment_id', $appointmentId)
            ->where('patient_id', $patient->patient_id)
            ->firstOrFail();

        if (!in_array($appointment->status, ['Requested', 'Pending Doctor Approval'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Can only update pending appointments.'],
            ]);
        }

        if (!empty($data['doctor_id']) && $data['doctor_id'] !== $appointment->doctor_id) {
            $doctor = Doctor::with('user')->findOrFail($data['doctor_id']);
            if ($doctor->user->clinic_id !== $actor->clinic_id) {
                throw new AuthorizationException('Doctor does not belong to your clinic.');
            }
        }

        if (!empty($data['appointment_date'])) {
            $doctorId = $data['doctor_id'] ?? $appointment->doctor_id;
            $this->assertSlotAvailable($doctorId, $data['appointment_date'], $appointmentId);
        }

        $appointment->update($data);

        return $appointment->fresh(['doctor.user', 'patient.user', 'clinic']);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function cancel(User $actor, int $appointmentId): Appointment
    {
        $appointment = $this->show($actor, $appointmentId);

        if (in_array($appointment->status, ['Completed', 'Cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Cannot cancel this appointment.'],
            ]);
        }

        $appointment->update(['status' => 'Cancelled']);

        return $appointment;
    }

    /**
     * @return Collection<int, Doctor>
     *
     * @throws AuthorizationException
     */
    public function availableDoctors(User $actor): Collection
    {
        if ($actor->role !== 'Patient' || !$actor->clinic_id) {
            throw new AuthorizationException('Only patients can view available doctors.');
        }

        return User::where('clinic_id', $actor->clinic_id)
            ->where('role', 'Doctor')
            ->where('status', 'Active')
            ->with('doctor')
            ->get()
            ->map(fn (User $user) => $user->doctor)
            ->filter();
    }

    private function resolvePatient(User $actor): Patient
    {
        if ($actor->role !== 'Patient') {
            throw new AuthorizationException('Only patients can manage their appointments.');
        }

        $patient = Patient::where('user_id', $actor->user_id)->first();

        if (!$patient) {
            throw new AuthorizationException('Patient record not found.');
        }

        return $patient;
    }

    private function assertSlotAvailable(int $doctorId, string $appointmentDate, ?int $ignoreAppointmentId = null): void
    {
        $query = Appointment::where('doctor_id', $doctorId)
            ->where('appointment_date', $appointmentDate)
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved']);

        if ($ignoreAppointmentId) {
            $query->where('appointment_id', '!=', $ignoreAppointmentId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'appointment_date' => ['This time slot is already booked. Please choose another time.'],
            ]);
        }
    }
}
