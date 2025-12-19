<?php

namespace App\Services\Admin;

use App\Models\Clinic;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    private const PLAN_REVENUE = [
        'Basic' => 100,
        'Standard' => 200,
        'Premium' => 300,
    ];

    public function stats(): array
    {
        $clinics = Clinic::withCount('users')->get();

        $monthlyRevenue = $clinics->sum(function (Clinic $clinic) {
            return self::PLAN_REVENUE[$clinic->subscription_plan] ?? 0;
        });

        return [
            'total_clinics' => $clinics->count(),
            'active_users' => User::where('status', 'Active')->count(),
            'total_patients' => Patient::count(),
            'monthly_revenue' => $monthlyRevenue,
            'recent_clinics' => $this->recentClinics($clinics),
            'revenue_history' => $this->revenueHistory($monthlyRevenue),
            'clinic_growth' => $this->clinicGrowth(),
        ];
    }

    private function recentClinics(Collection $clinics): Collection
    {
        return $clinics
            ->sortByDesc('created_at')
            ->take(5)
            ->values();
    }

    private function revenueHistory(int $monthlyRevenue): Collection
    {
        $history = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');

            // Provide a slight deterministic variation using the month index.
            $variation = (5 - $i) * 0.02; // 0% to 10% increase across months
            $historicalRevenue = $monthlyRevenue * (1 - ($i * 0.05)) * (1 + $variation);

            $history->push([
                'name' => $monthName,
                'revenue' => (int) round($historicalRevenue),
            ]);
        }

        return $history;
    }

    private function clinicGrowth(): Collection
    {
        $growth = collect();

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i)->endOfMonth();
            $monthName = $date->format('M');

            $count = Clinic::where('created_at', '<=', $date)->count();

            $growth->push([
                'name' => $monthName,
                'clinics' => $count,
            ]);
        }

        return $growth;
    }
}
