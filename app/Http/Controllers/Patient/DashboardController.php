<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Resources\Patient\PatientHistoryResource;
use App\Services\Patient\PatientDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly PatientDashboardService $dashboardService)
    {
    }

    /**
     * Get dashboard statistics for the authenticated patient
     */
    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats retrieved successfully.',
            'data' => $this->dashboardService->stats($request->user()),
        ], 200);
    }
    /**
     * Get authenticated patient's medical history
     */
    public function history(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Medical history retrieved successfully.',
            'data' => PatientHistoryResource::collection(
                $this->dashboardService->history($request->user())
            ),
        ]);
    }
}
