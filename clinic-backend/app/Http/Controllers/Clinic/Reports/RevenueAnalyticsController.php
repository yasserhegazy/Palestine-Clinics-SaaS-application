<?php

namespace App\Http\Controllers\Clinic\Reports;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevenueAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $period = $request->get('period', 'month');
        
        $data = [
            'total_revenue' => $this->getTotalRevenue($clinicId, $period),
            'revenue_trend' => $this->getRevenueTrend($clinicId, $period),
            'payment_methods' => $this->getPaymentMethodsBreakdown($clinicId, $period),
            'daily_revenue' => $this->getDailyRevenue($clinicId, $period)
        ];

        return response()->json($data);
    }

    private function getTotalRevenue($clinicId, $period)
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->sum('amount');
    }

    private function getRevenueTrend($clinicId, $period)
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->selectRaw('DATE(payment_date) as date, SUM(amount_paid) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    private function getPaymentMethodsBreakdown($clinicId, $period)
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->selectRaw('payment_method, SUM(amount_paid) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get();
    }

    private function getDailyRevenue($clinicId, $period)
    {
        $query = Payment::where('clinic_id', $clinicId)
            ->where('status', 'Paid');

        $this->applyPeriodFilter($query, $period);

        return $query->selectRaw('DATE(payment_date) as date, SUM(amount_paid) as revenue, COUNT(*) as transactions')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    private function applyPeriodFilter($query, $period)
    {
        switch ($period) {
            case 'week':
                $query->where('payment_date', '>=', Carbon::now()->subWeek());
                break;
            case 'month':
                $query->where('payment_date', '>=', Carbon::now()->subMonth());
                break;
            case 'quarter':
                $query->where('payment_date', '>=', Carbon::now()->subQuarter());
                break;
            case 'year':
                $query->where('payment_date', '>=', Carbon::now()->subYear());
                break;
        }
    }
}
