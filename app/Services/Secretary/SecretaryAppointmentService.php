<?php

namespace App\Services\Secretary;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SecretaryAppointmentService
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * Get all pending appointment requests for the clinic.
     *
     * @return Collection<int, Appointment>
     */
    public function requests(User $actor): Collection
    {
        $this->ensureSecretary($actor);

        return Appointment::where('clinic_id', $actor->clinic_id)
            ->byStatus('Requested')
            ->with(['patient.user', 'doctor.user', 'clinic'])
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get();
    }

    /**
     * Approve appointment request.
     */
    public function approve(User $actor, int $appointmentId): Appointment
    {
        $appointment = $this->findForSecretary($actor, $appointmentId);

        if ($appointment->status !== 'Requested') {
            throw ValidationException::withMessages([
                'status' => ['Only requested appointments can be approved.'],
            ]);
        }

        $appointment->status = 'Approved';
        $appointment->secretary_id = $actor->user_id;
        $appointment->save();

        return $appointment->fresh(['patient.user', 'doctor.user', 'clinic']);
    }

    /**
     * Reject appointment request.
     */
    public function reject(User $actor, int $appointmentId, string $reason): Appointment
    {
        $appointment = $this->findForSecretary($actor, $appointmentId);

        if ($appointment->status !== 'Requested') {
            throw ValidationException::withMessages([
                'status' => ['Only requested appointments can be rejected.'],
            ]);
        }

        $appointment->status = 'Cancelled';
        $appointment->rejection_reason = $reason;
        $appointment->secretary_id = $actor->user_id;
        $appointment->save();

        return $appointment->fresh(['patient.user', 'doctor.user', 'clinic']);
    }

    /**
     * Reschedule appointment request.
     */
    public function reschedule(User $actor, int $appointmentId, array $data): Appointment
    {
        $appointment = $this->findForSecretary($actor, $appointmentId);

        $appointment->appointment_date = $data['appointment_date'];
        $appointment->appointment_time = $data['appointment_time'];
        $appointment->status = 'Approved';
        $appointment->secretary_id = $actor->user_id;
        $appointment->save();

        return $appointment->fresh(['patient.user', 'doctor.user', 'clinic']);
    }

    /**
     * @throws AuthorizationException
     */
    private function ensureSecretary(User $actor): void
    {
        if ($actor->role !== 'Secretary') {
            throw new AuthorizationException('Only secretaries can access this resource.');
        }

        if (!$actor->clinic_id) {
            throw new AuthorizationException('Secretary must be associated with a clinic.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function findForSecretary(User $actor, int $appointmentId): Appointment
    {
        $this->ensureSecretary($actor);

        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->clinic_id !== $actor->clinic_id) {
            throw new AuthorizationException('You do not have permission to manage this appointment.');
        }

        return $appointment;
    }
}
