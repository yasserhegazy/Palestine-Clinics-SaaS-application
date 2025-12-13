<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Get comprehensive clinic reports
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $startDate = $request->get('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', today()->format('Y-m-d'));

        // Get payments for the period
        $payments = Payment::where('clinic_id', $user->clinic_id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['patient.user', 'appointment.doctor.user'])
            ->get();

        // Get appointments for the period
        $appointments = Appointment::where('clinic_id', $user->clinic_id)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->with(['patient.user', 'doctor.user'])
            ->get();

        // Calculate financial metrics
        $totalRevenue = $payments->where('status', 'Paid')->sum('amount_paid');
        $totalPending = $payments->whereIn('status', ['Pending', 'Partial'])
            ->sum(function ($payment) {
                return ($payment->amount ?? 0) - ($payment->amount_paid ?? 0);
            });

        // Get clinic statistics
        // Patients don't have a clinic_id column; they are linked through their user
        $totalPatients = Patient::whereHas('user', function ($query) use ($user) {
            $query->where('clinic_id', $user->clinic_id);
        })->count();

        // Use status column for active staff (users table has status, not is_active)
        $activeDoctors = User::where('clinic_id', $user->clinic_id)
            ->where('role', 'Doctor')
            ->where('status', 'Active')
            ->count();
        $employees = User::where('clinic_id', $user->clinic_id)
            ->whereIn('role', ['Secretary', 'Manager'])
            ->where('status', 'Active')
            ->count();
        $todayAppointments = Appointment::where('clinic_id', $user->clinic_id)
            ->whereDate('appointment_date', today())
            ->count();

        // Monthly revenue (current month)
        $monthlyRevenue = Payment::where('clinic_id', $user->clinic_id)
            ->whereMonth('payment_date', today()->month)
            ->whereYear('payment_date', today()->year)
            ->where('status', 'Paid')
            ->sum('amount_paid');

        return response()->json([
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'financial' => [
                'total_revenue' => $totalRevenue,
                'total_pending' => $totalPending,
                'monthly_revenue' => $monthlyRevenue,
                'paid_payments' => $payments->where('status', 'Paid')->count(),
                'pending_payments' => $payments->whereIn('status', ['Pending', 'Partial'])->count(),
                'total_payments' => $payments->count(),
            ],
            'clinic_stats' => [
                'total_patients_count' => $totalPatients,
                'active_doctors_count' => $activeDoctors,
                'employees_count' => $employees,
                'today_appointments_count' => $todayAppointments,
            ],
            'appointments_summary' => [
                'total' => $appointments->count(),
                'completed' => $appointments->where('status', 'Completed')->count(),
                'pending' => $appointments->where('status', 'Pending')->count(),
                'approved' => $appointments->where('status', 'Approved')->count(),
                'cancelled' => $appointments->where('status', 'Cancelled')->count(),
            ],
        ], 200);
    }

    /**
     * Get daily financial summary
     */
    public function dailyFinancial(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $date = $request->get('date', today()->format('Y-m-d'));

        $payments = Payment::where('clinic_id', $user->clinic_id)
            ->whereDate('payment_date', $date)
            ->with(['patient.user', 'receiver'])
            ->get();

        $summary = [
            'date' => $date,
            'total_collected' => $payments->where('status', 'Paid')->sum('amount_paid'),
            'total_pending' => $payments->where('status', 'Pending')->sum('amount'),
            'cash_transactions' => $payments->where('payment_method', 'Cash')->count(),
            'total_transactions' => $payments->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'payments' => $payments,
        ], 200);
    }

    /**
     * Get revenue analytics for multiple days
     */
    public function revenueAnalytics(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $days = $request->get('days', 30);
        $endDate = today();
        $startDate = $endDate->copy()->subDays($days - 1);

        $analytics = [];
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayPayments = Payment::where('clinic_id', $user->clinic_id)
                ->whereDate('payment_date', $date)
                ->get();

            $analytics[] = [
                'date' => $date->format('Y-m-d'),
                'revenue' => $dayPayments->where('status', 'Paid')->sum('amount_paid'),
                'transactions' => $dayPayments->count(),
            ];
        }

        return response()->json([
            'analytics' => $analytics,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $days
            ]
        ], 200);
    }
}
