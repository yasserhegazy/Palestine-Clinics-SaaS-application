<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Payment\DailyPaymentReportRequest;
use App\Services\Clinic\PaymentService;
use App\Services\Clinic\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyReportController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly ReportService $reportService
    ) {
    }

    /**
     * Get daily payment report for secretary
     */
    public function dailyReport(DailyPaymentReportRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Daily payment report retrieved successfully.',
            'data' => $this->paymentService->dailyReport($request->user(), $request->reportDate()),
        ], 200);
    }

    /**
     * Get appointments summary for the day
     */
    public function appointmentsSummary(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Appointments summary retrieved successfully.',
            'data' => $this->reportService->summary(
                $request->user(),
                $request->get('date', today()->format('Y-m-d')),
                $request->get('date', today()->format('Y-m-d'))
            ),
        ], 200);
    }
}
