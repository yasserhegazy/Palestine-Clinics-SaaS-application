<?php

namespace App\Jobs;

use App\Events\Appointments\DoctorHasTodayAppointments;
use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchTodayDoctorAppointmentsNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $today = now()->toDateString();

        $grouped = [];

        Appointment::whereDate('appointment_date', $today)
            ->where('status', 'Approved')
            ->with(['doctor.user', 'patient.user', 'clinic'])
            ->chunkById(200, function ($chunk) use (&$grouped) {
                foreach ($chunk as $appointment) {
                    $doctor = $appointment->doctor;
                    $doctorUser = $doctor?->user;
                    if (!$doctorUser) {
                        continue;
                    }

                    $grouped[$doctorUser->user_id]['user'] = $doctorUser;
                    $grouped[$doctorUser->user_id]['appointments'][] = [
                        'appointment_id' => $appointment->appointment_id,
                        'appointment_time' => $appointment->appointment_time,
                        'patient_name' => $appointment->patient?->user?->name,
                        'clinic_name' => $appointment->clinic?->name,
                    ];
                }
            });

        foreach ($grouped as $doctorData) {
            $appointments = $doctorData['appointments'] ?? [];
            $doctorUser = $doctorData['user'] ?? null;

            if ($doctorUser && !empty($appointments)) {
                DoctorHasTodayAppointments::dispatch(
                    $doctorUser,
                    now()->toDateString(),
                    count($appointments),
                    $appointments
                );
            }
        }
    }
}
