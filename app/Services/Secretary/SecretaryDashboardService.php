<?php

namespace App\Services\Secretary;

use App\Models\Appointment;
use Carbon\Carbon;

class SecretaryDashboardService
{
    /**
     * Get dashboard statistics for secretary
     *
     * @param int $clinicId
     * @return array
     */
    public function getStats(int $clinicId): array
    {
        $today = Carbon::today();

        // Today's appointments count (all statuses)
        $todayAppointments = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->count();

        // Waiting patients (Approved appointments for today that haven't been completed)
        $waitingPatients = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->where('status', 'Approved')
            ->count();

        // Pending appointment requests
        $pendingRequests = Appointment::where('clinic_id', $clinicId)
            ->where('status', 'Requested')
            ->count();

        // Check-ins today (Approved or Completed appointments)
        $checkinsToday = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['Approved', 'Completed'])
            ->count();

        return [
            'checkins_today' => $checkinsToday,
            'scheduled_appointments' => $todayAppointments,
            'waiting_patients' => $waitingPatients,
            'pending_requests' => $pendingRequests,
        ];
    }

    /**
     * Get today's appointments for secretary
     *
     * @param int $clinicId
     * @return array
     */
    public function getTodayAppointments(int $clinicId): array
    {
        $today = Carbon::today();

        $appointments = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->with(['patient.user', 'doctor.user'])
            ->orderBy('appointment_time')
            ->get();

        return $appointments->map(function ($appointment) {
            return [
                'id' => $appointment->appointment_id,
                'time' => $appointment->appointment_time ? substr($appointment->appointment_time, 0, 5) : null,
                'patient' => $appointment->patient?->user?->name ?? 'N/A',
                'doctor' => $appointment->doctor?->user?->name ?? 'N/A',
                'type' => $appointment->doctor?->specialization ?? 'General',
                'status' => $appointment->status,
                'notes' => $appointment->notes,
            ];
        })->toArray();
    }

    /**
     * Get waiting room data for secretary
     *
     * @param int $clinicId
     * @return array
     */
    public function getWaitingRoom(int $clinicId): array
    {
        $today = Carbon::today();

        // Get approved appointments for today that haven't been completed
        $waitingAppointments = Appointment::where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $today)
            ->where('status', 'Approved')
            ->with(['patient.user'])
            ->orderBy('appointment_time')
            ->get();

        return $waitingAppointments->map(function ($appointment, $index) {
            // Parse the appointment date and time correctly
            $appointmentDate = Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            $appointmentTime = $appointment->appointment_time ?? '00:00:00';
            $appointmentDateTime = Carbon::parse($appointmentDate . ' ' . $appointmentTime);
            
            $now = Carbon::now();
            $waitingMinutes = (int) abs($now->diffInMinutes($appointmentDateTime, false));

            return [
                'id' => $appointment->appointment_id,
                'patient' => $appointment->patient?->user?->name ?? 'N/A',
                'ticket' => 'A' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'waiting_minutes' => $waitingMinutes,
                'appointment_time' => $appointment->appointment_time ? substr($appointment->appointment_time, 0, 5) : null,
            ];
        })->toArray();
    }
}
