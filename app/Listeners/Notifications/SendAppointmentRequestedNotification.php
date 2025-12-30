<?php

namespace App\Listeners\Notifications;

use App\Events\Appointments\AppointmentRequested;
use App\Notifications\AppointmentRequestedNotification;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAppointmentRequestedNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(AppointmentRequested $event): void
    {
        $appointment = $event->appointment->loadMissing(['doctor.user', 'patient.user', 'clinic']);

        $doctorUser = $appointment->doctor?->user;

        if ($doctorUser) {
            DB::transaction(function () use ($doctorUser, $appointment) {
                $exists = $doctorUser->notifications()
                    ->where('type', AppointmentRequestedNotification::class)
                    ->where('data->appointment_id', $appointment->appointment_id)
                    ->lockForUpdate()
                    ->exists();

                if (!$exists) {
                    $this->notifications->send(
                        $doctorUser,
                        new AppointmentRequestedNotification($appointment)
                    );
                }
            });
        }
    }
}
