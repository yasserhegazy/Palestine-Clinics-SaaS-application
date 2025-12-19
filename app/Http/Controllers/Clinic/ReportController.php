<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Report\DailyFinancialRequest;
use App\Http\Requests\Clinic\Report\ReportPeriodRequest;
use App\Http\Requests\Clinic\Report\RevenueAnalyticsPeriodRequest;
use App\Services\Clinic\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    /**
     * Get comprehensive clinic reports.
     */
    public function index(ReportPeriodRequest $request): JsonResponse
    {
        $period = $request->period();

        return response()->json([
            'success' => true,
            'message' => 'Clinic report retrieved successfully.',
            'data' => $this->reportService->summary(
                $request->user(),
                $period['start_date'],
                $period['end_date']
            ),
        ], 200);
    }

    /**
     * Get daily financial summary.
     */
    public function dailyFinancial(DailyFinancialRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daily financial summary retrieved successfully.',
            'data' => $this->reportService->dailyFinancial(
                $request->user(),
                $request->dateValue()
            ),
        ], 200);
    }

    /**
     * Get revenue analytics for multiple days.
     */
    public function revenueAnalytics(RevenueAnalyticsPeriodRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Revenue analytics retrieved successfully.',
            'data' => $this->reportService->revenueAnalytics(
                $request->user(),
                $request->days()
            ),
        ], 200);
    }
}
