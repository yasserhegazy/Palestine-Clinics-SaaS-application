<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $user = $request->user();

        // Verify user is a manager or clinic admin
        // Note: The role check is also handled by middleware, but good to have context.
        $clinicId = $user->clinic_id;

        if (!$clinicId) {
            return response()->json(['error' => 'User not associated with a clinic'], 400);
        }

        // 1. Employees Count (Doctors + Secretaries associated with this clinic)
        // Assuming 'Doctor' and 'Secretary' roles are what we count.
        // We need to count users who belong to this clinic and represent staff.
        $employeesCount = User::where('clinic_id', $clinicId)
            ->whereIn('role', ['Doctor', 'Secretary'])
            ->where('status', 'Active') // Optional: only active employees
            ->count();

        // 2. Today's Appointments Count
        $todayAppointmentsCount = Appointment::where('clinic_id', $clinicId)
            ->today() // Using the scope from Appointment model
            ->count();
        
        // 3. Total Patients Count
        // Patients are linked to clinics roughly via appointments or via the user-clinic relationship if they are registered *for* a clinic.
        // Looking at PatientController, `createPatient` creates a User and Patient linked to the *clinic* of the logged-in user.
        // So we can count patients whose user record has clinic_id = current clinic.
        // Wait, Patient model belongsTo User. User has clinic_id.
        $totalPatientsCount = Patient::whereHas('user', function ($query) use ($clinicId) {
            $query->where('clinic_id', $clinicId);
        })->count();

        // 4. Monthly Revenue (Estimated)
        // If we don't have a transaction table yet, we can't calculate real revenue.
        // The user asked for "like the admin", where we mocked it or calculated from subscriptions.
        // For a clinic, revenue comes from appointments. 
        // If appointments don't have a 'price' field, we might need to mock this or sum up completed appointments * fixed rate.
        // Let's check Appointment model again... no price field visible in fillable using view_file previously.
        // I'll return a placeholder or 0 for now to avoid breaking, or maybe a count of completed appointments this month.
        // Let's just return 0 or a mock value based on completed appointments * 50 (example rate).
        $monthlyCompletedAppointments = Appointment::where('clinic_id', $clinicId)
            ->where('status', 'Completed')
            ->whereMonth('appointment_date', Carbon::now()->month)
            ->whereYear('appointment_date', Carbon::now()->year)
            ->count();
            
        $estimatedMonthlyRevenue = $monthlyCompletedAppointments * 100; // Assuming 100 per appointment for estimation

        // 5. Active Doctors (for the "Active Doctors" card I saw in frontend)
        $activeDoctorsCount = User::where('clinic_id', $clinicId)
            ->where('role', 'Doctor')
            ->where('status', 'Active')
            ->count();

        return response()->json([
            'employees_count' => $employeesCount,
            'today_appointments_count' => $todayAppointmentsCount,
            'total_patients_count' => $totalPatientsCount,
            'monthly_revenue' => $estimatedMonthlyRevenue,
            'active_doctors_count' => $activeDoctorsCount
        ]);
    }
}
