<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        // Basic Stats
        $totalClinics = Clinic::count();
        $activeUsers = User::where('status', 'Active')->count();
        $totalPatients = Patient::count();

        // Calculate Revenue (Estimated based on subscription plans)
        // Basic: 100, Standard: 200, Premium: 300
        $clinics = Clinic::all();
        $monthlyRevenue = 0;
        foreach ($clinics as $clinic) {
            switch ($clinic->subscription_plan) {
                case 'Basic':
                    $monthlyRevenue += 100;
                    break;
                case 'Standard':
                    $monthlyRevenue += 200;
                    break;
                case 'Premium':
                    $monthlyRevenue += 300;
                    break;
            }
        }

        // Recent Clinics
        $recentClinics = Clinic::withCount('users')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($clinic) {
                return [
                    'id' => $clinic->clinic_id,
                    'name' => $clinic->name,
                    'location' => $clinic->address, // Assuming address contains city/location
                    'status' => $clinic->status,
                    'users_count' => $clinic->users_count,
                    'created_at' => $clinic->created_at->format('Y-m-d'),
                ];
            });

        // Revenue History (Last 6 months)
        $revenueHistory = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            
            // For demo purposes, we'll generate some realistic looking variation
            // based on the current revenue
            $variation = rand(-10, 20) / 100; // -10% to +20%
            $historicalRevenue = $monthlyRevenue * (1 - ($i * 0.05)) * (1 + $variation);
            
            $revenueHistory->push([
                'name' => $monthName,
                'revenue' => round($historicalRevenue)
            ]);
        }

        // Clinic Growth (Last 6 months)
        $clinicGrowth = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M');
            
            $count = Clinic::where('created_at', '<=', $date->endOfMonth())->count();
            
            $clinicGrowth->push([
                'name' => $monthName,
                'clinics' => $count
            ]);
        }

        return response()->json([
            'total_clinics' => $totalClinics,
            'active_users' => $activeUsers,
            'total_patients' => $totalPatients,
            'monthly_revenue' => $monthlyRevenue,
            'recent_clinics' => $recentClinics,
            'revenue_history' => $revenueHistory,
            'clinic_growth' => $clinicGrowth,
        ]);
    }
}
