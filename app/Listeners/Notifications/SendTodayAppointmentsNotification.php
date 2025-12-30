<?php

namespace App\Listeners\Notifications;

use App\Events\Appointments\DoctorHasTodayAppointments;
use App\Notifications\TodayAppointmentsNotification;
use App\Services\Notifications\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTodayAppointmentsNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function handle(DoctorHasTodayAppointments $event): void
    {
        $this->notifications->send(
            $event->doctorUser,
            new TodayAppointmentsNotification(
                $event->date,
                $event->count,
                $event->appointments
            )
        );
    }
}
