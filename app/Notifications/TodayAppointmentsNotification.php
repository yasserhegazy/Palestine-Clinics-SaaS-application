<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TodayAppointmentsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param array<int, array{appointment_id:int, appointment_time:string, patient_name:string|null, clinic_name:?string}> $appointments
     */
    public function __construct(
        private readonly string $date,
        private readonly int $count,
        private readonly array $appointments
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $preview = array_slice($this->appointments, 0, 3);

        return [
            'title' => 'Today’s appointments',
            'body' => sprintf('You have %d appointment(s) on %s.', $this->count, $this->date),
            'category' => 'today_schedule',
            'count' => $this->count,
            'appointments' => $preview,
            'cta' => [
                'label' => 'View schedule',
                'path' => '/doctor/appointments/today',
            ],
        ];
    }
}
