<?php

namespace App\Http\Controllers\Clinic\Reports;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Report\RevenueAnalyticsPeriodRequest;
use App\Services\Clinic\ReportService;
use Illuminate\Http\JsonResponse;

class RevenueAnalyticsController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function index(RevenueAnalyticsPeriodRequest $request): JsonResponse
    {
        $data = $this->reportService->revenueAnalyticsByPeriod(
            $request->user(),
            $request->period()
        );

        return response()->json([
            'success' => true,
            'message' => 'Revenue analytics retrieved successfully.',
            'data' => $data,
        ]);
    }
}
