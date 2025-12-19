<?php

namespace App\Services\Manager;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class ManagerDashboardService
{
    /**
     * @throws AuthorizationException
     */
    public function stats(User $manager): array
    {
        $clinicId = $this->resolveClinicId($manager);

        $employeesCount = User::where('clinic_id', $clinicId)
            ->whereIn('role', ['Doctor', 'Secretary'])
            ->where('status', 'Active')
            ->count();

        $todayAppointmentsCount = Appointment::where('clinic_id', $clinicId)
            ->today()
            ->count();

        $totalPatientsCount = Patient::whereHas('user', function ($query) use ($clinicId) {
            $query->where('clinic_id', $clinicId);
        })->count();

        $monthlyRevenue = Payment::where('clinic_id', $clinicId)
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->where('status', 'Paid')
            ->sum('amount_paid');

        $activeDoctorsCount = User::where('clinic_id', $clinicId)
            ->where('role', 'Doctor')
            ->where('status', 'Active')
            ->count();

        return [
            'employees_count' => $employeesCount,
            'today_appointments_count' => $todayAppointmentsCount,
            'total_patients_count' => $totalPatientsCount,
            'monthly_revenue' => $monthlyRevenue,
            'active_doctors_count' => $activeDoctorsCount,
        ];
    }

    /**
     * @throws AuthorizationException
     */
    private function resolveClinicId(User $manager): int
    {
        if ($manager->role !== 'Manager' || !$manager->clinic_id) {
            throw new AuthorizationException('User not associated with a clinic.');
        }

        return (int) $manager->clinic_id;
    }
}
