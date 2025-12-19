<?php

namespace App\Services\Clinic;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function summary(User $actor, string $startDate, string $endDate): array
    {
        $this->ensureActorHasClinic($actor);

        $payments = Payment::where('clinic_id', $actor->clinic_id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->with(['patient.user', 'appointment.doctor.user'])
            ->get();

        $appointments = Appointment::where('clinic_id', $actor->clinic_id)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->with(['patient.user', 'doctor.user'])
            ->get();

        $totalRevenue = $payments->where('status', 'Paid')->sum('amount_paid');
        $totalPending = $payments->whereIn('status', ['Pending', 'Partial'])
            ->sum(static fn ($payment) => ($payment->amount ?? 0) - ($payment->amount_paid ?? 0));

        $totalPatients = Patient::whereHas('user', function ($query) use ($actor) {
            $query->where('clinic_id', $actor->clinic_id);
        })->count();

        $activeDoctors = User::where('clinic_id', $actor->clinic_id)
            ->where('role', 'Doctor')
            ->where('status', 'Active')
            ->count();

        $employees = User::where('clinic_id', $actor->clinic_id)
            ->whereIn('role', ['Secretary', 'Manager'])
            ->where('status', 'Active')
            ->count();

        $todayAppointments = Appointment::where('clinic_id', $actor->clinic_id)
            ->whereDate('appointment_date', today())
            ->count();

        $monthlyRevenue = Payment::where('clinic_id', $actor->clinic_id)
            ->whereMonth('payment_date', today()->month)
            ->whereYear('payment_date', today()->year)
            ->where('status', 'Paid')
            ->sum('amount_paid');

        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function dailyFinancial(User $actor, string $date): array
    {
        $this->ensureActorHasClinic($actor);

        $payments = Payment::where('clinic_id', $actor->clinic_id)
            ->whereDate('payment_date', $date)
            ->with(['patient.user', 'receiver'])
            ->get();

        return [
            'summary' => [
                'date' => $date,
                'total_collected' => $payments->where('status', 'Paid')->sum('amount_paid'),
                'total_pending' => $payments->where('status', 'Pending')->sum('amount'),
                'cash_transactions' => $payments->where('payment_method', 'Cash')->count(),
                'total_transactions' => $payments->count(),
            ],
            'payments' => $payments,
        ];
    }

    /**
     * @return array{analytics: Collection<int, array<string, mixed>>, period: array<string, mixed>}
     *
     * @throws AuthorizationException
     */
    public function revenueAnalytics(User $actor, int $days): array
    {
        $this->ensureActorHasClinic($actor);

        $endDate = today();
        $startDate = $endDate->copy()->subDays($days - 1);

        $analytics = collect();
        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            $dayPayments = Payment::where('clinic_id', $actor->clinic_id)
                ->whereDate('payment_date', $date)
                ->get();

            $analytics->push([
                'date' => $date->format('Y-m-d'),
                'revenue' => $dayPayments->where('status', 'Paid')->sum('amount_paid'),
                'transactions' => $dayPayments->count(),
            ]);
        }

        return [
            'analytics' => $analytics,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function revenueAnalyticsByPeriod(User $actor, string $period): array
    {
        $this->ensureActorHasClinic($actor);
        $clinicId = $actor->clinic_id;

        $totalRevenue = $this->calculateTotalRevenue($clinicId, $period);
        $revenueTrend = $this->revenueTrend($clinicId, $period);
        $paymentMethods = $this->paymentMethodBreakdown($clinicId, $period);
        $dailyRevenue = $this->dailyRevenue($clinicId, $period);

        return [
            'total_revenue' => $totalRevenue,
            'revenue_trend' => $revenueTrend,
            'payment_methods' => $paymentMethods,
            'daily_revenue' => $dailyRevenue,
        ];
    }

    private function calculateTotalRevenue(int $clinicId, string $period): float
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return (float) $query->sum('amount');
    }

    private function revenueTrend(int $clinicId, string $period): Collection
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->selectRaw('DATE(payment_date) as date, SUM(amount_paid) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function paymentMethodBreakdown(int $clinicId, string $period): Collection
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->selectRaw('payment_method, SUM(amount_paid) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();
    }

    private function dailyRevenue(int $clinicId, string $period): Collection
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->selectRaw('DATE(payment_date) as date, SUM(amount_paid) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    private function applyPeriodFilter($query, string $period): void
    {
        $now = Carbon::now();

        switch ($period) {
            case 'week':
                $query->where('payment_date', '>=', $now->copy()->subWeek());
                break;
            case 'month':
                $query->where('payment_date', '>=', $now->copy()->subMonth());
                break;
            case 'quarter':
                $query->where('payment_date', '>=', $now->copy()->subQuarter());
                break;
            case 'year':
                $query->where('payment_date', '>=', $now->copy()->subYear());
                break;
            default:
                $query->where('payment_date', '>=', $now->copy()->subMonth());
                break;
        }
    }

    private function ensureActorHasClinic(User $actor): void
    {
        if (!$actor->clinic_id) {
            throw new AuthorizationException('You are not associated with any clinic.');
        }
    }
}
