<?php

namespace App\Services\Doctor;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DoctorAppointmentService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * @return array{doctor: Doctor, appointments: Collection}
     */
    public function todayAppointments(User $actor): array
    {
        $doctor = $this->resolveDoctor($actor);

        $appointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->byStatus('Approved')
            ->today()
            ->with(['patient.user', 'clinic'])
            ->orderBy('appointment_time')
            ->get();

        return ['doctor' => $doctor, 'appointments' => $appointments];
    }

    /**
     * @return array{doctor: Doctor, appointments: Collection}
     */
    public function upcomingAppointments(User $actor): array
    {
        $doctor = $this->resolveDoctor($actor);

        $appointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->where('status', 'Approved')
            ->whereDate('appointment_date', '>', now()->format('Y-m-d'))
            ->with(['patient.user', 'clinic'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();

        return ['doctor' => $doctor, 'appointments' => $appointments];
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function list(User $actor, array $filters): Collection
    {
        $doctor = $this->resolveDoctor($actor);

        $query = Appointment::where('doctor_id', $doctor->doctor_id);

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('appointment_date', $filters['date']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        return $query->with(['patient.user', 'clinic'])
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time')
            ->get();
    }

    /**
     * Pending/Requested appointments for the doctor.
     *
     * @return Collection<int, Appointment>
     */
    public function requests(User $actor): Collection
    {
        $doctor = $this->resolveDoctor($actor);

        return Appointment::where('doctor_id', $doctor->doctor_id)
            ->byStatus('Requested')
            ->with(['patient.user', 'clinic'])
            ->orderBy('appointment_date')
            ->get();
    }

    /**
     * @return array{appointment: Appointment, next_appointment: ?Appointment, medical_record: ?MedicalRecord}
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function complete(User $actor, int $appointmentId, array $data): array
    {
        $doctor = $this->resolveDoctor($actor);

        $appointment = Appointment::with('patient')->findOrFail($appointmentId);

        if ($appointment->doctor_id !== $doctor->doctor_id) {
            throw new AuthorizationException('You do not have permission to complete this appointment.');
        }

        if ($appointment->status !== 'Approved') {
            throw ValidationException::withMessages([
                'status' => ['Only approved appointments can be completed.'],
            ]);
        }

        return $this->database->transaction(function () use ($appointment, $doctor, $data) {
            $appointment->status = 'Completed';
            $appointment->save();

            $medicalRecord = MedicalRecord::create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $doctor->doctor_id,
                'visit_date' => $data['visit_date'] ?? now(),
                'symptoms' => $data['symptoms'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'prescription' => $data['prescription'] ?? '',
                'next_visit' => $data['next_visit'] ?? null,
            ]);

            $nextAppointment = null;
            if (!empty($data['create_next_appointment'])) {
                $nextAppointment = $this->createNextAppointment($doctor, $appointment);
            }

            return [
                'appointment' => $appointment,
                'next_appointment' => $nextAppointment,
                'medical_record' => $medicalRecord,
            ];
        });
    }

    /**
     * Approve appointment.
     */
    public function approve(User $actor, int $appointmentId): Appointment
    {
        $appointment = $this->findForDoctor($actor, $appointmentId);

        if ($appointment->status !== 'Requested') {
            throw ValidationException::withMessages([
                'status' => ['Only requested appointments can be approved.'],
            ]);
        }

        $appointment->status = 'Approved';
        $appointment->save();

        return $appointment->fresh(['patient.user', 'clinic']);
    }

    public function reject(User $actor, int $appointmentId, string $reason): Appointment
    {
        $appointment = $this->findForDoctor($actor, $appointmentId);

        if ($appointment->status !== 'Requested') {
            throw ValidationException::withMessages([
                'status' => ['Only requested appointments can be rejected.'],
            ]);
        }

        $appointment->status = 'Cancelled';
        $appointment->rejection_reason = $reason;
        $appointment->save();

        return $appointment->fresh(['patient.user', 'clinic']);
    }

    public function reschedule(User $actor, int $appointmentId, array $data): Appointment
    {
        $appointment = $this->findForDoctor($actor, $appointmentId);

        $appointment->appointment_date = $data['appointment_date'];
        $appointment->appointment_time = $data['appointment_time'];
        $appointment->status = 'Approved';
        $appointment->save();

        return $appointment->fresh(['patient.user', 'clinic']);
    }

    /**
     * @throws AuthorizationException
     */
    private function resolveDoctor(User $actor): Doctor
    {
        if ($actor->role !== 'Doctor') {
            throw new AuthorizationException('Only doctors can access this resource.');
        }

        $doctor = Doctor::where('user_id', $actor->user_id)->first();

        if (!$doctor) {
            throw new AuthorizationException('Doctor profile not found.');
        }

        return $doctor;
    }

    private function findForDoctor(User $actor, int $appointmentId): Appointment
    {
        $doctor = $this->resolveDoctor($actor);

        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->doctor_id !== $doctor->doctor_id) {
            throw new AuthorizationException('You do not have permission to manage this appointment.');
        }

        return $appointment;
    }

    private function createNextAppointment(Doctor $doctor, Appointment $completedAppointment): ?Appointment
    {
        $searchDate = now()->addDay()->startOfDay();
        $maxDaysToSearch = 30;
        $daysSearched = 0;

        while ($daysSearched < $maxDaysToSearch) {
            $dateString = $searchDate->format('Y-m-d');
            $availableSlots = $doctor->getAvailableSlots($dateString);

            if (!empty($availableSlots)) {
                $firstSlot = $availableSlots[0];

                return Appointment::create([
                    'patient_id' => $completedAppointment->patient_id,
                    'doctor_id' => $doctor->doctor_id,
                    'clinic_id' => $completedAppointment->clinic_id,
                    'appointment_date' => $dateString,
                    'appointment_time' => $firstSlot['start'],
                    'status' => 'Approved',
                    'notes' => 'Follow-up appointment (auto-scheduled)',
                ]);
            }

            $searchDate->addDay();
            $daysSearched++;
        }

        return null;
    }
}
