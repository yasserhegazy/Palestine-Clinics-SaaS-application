<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AppointmentRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Appointment $appointment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $patientName = optional($this->appointment->patient?->user)->name ?? 'Patient';
        $clinicName = optional($this->appointment->clinic)->name ?? null;
        $time = $this->appointment->appointment_time ?: 'time to be scheduled';

        return [
            'fingerprint' => $this->fingerprint(),
            'title' => 'New appointment request',
            'body' => sprintf(
                '%s requested %s at %s%s',
                $patientName,
                $this->appointment->appointment_date?->format('Y-m-d'),
                $time,
                $clinicName ? " • {$clinicName}" : ''
            ),
            'category' => 'appointment_request',
            'appointment_id' => $this->appointment->appointment_id,
            'clinic_id' => $this->appointment->clinic_id,
            'patient_id' => $this->appointment->patient_id,
            'cta' => [
                'label' => 'Review',
                'path' => '/appointments/requests',
            ],
        ];
    }

    /**
     * Deterministic fingerprint to prevent duplicates per doctor/appointment.
     */
    public function fingerprint(): string
    {
        return sprintf(
            'appointment_request:%s:%s',
            $this->appointment->appointment_id,
            $this->appointment->doctor_id
        );
    }
}
