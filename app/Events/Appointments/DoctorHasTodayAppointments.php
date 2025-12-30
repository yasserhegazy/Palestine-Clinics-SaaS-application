<?php

namespace App\Events\Appointments;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DoctorHasTodayAppointments
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<int, array{appointment_id:int, appointment_time:string, patient_name:?string, clinic_name:?string}> $appointments
     */
    public function __construct(
        public readonly User $doctorUser,
        public readonly string $date,
        public readonly int $count,
        public readonly array $appointments
    ) {
    }
}
