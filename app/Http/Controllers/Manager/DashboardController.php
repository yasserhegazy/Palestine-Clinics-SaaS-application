<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Services\Manager\ManagerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ManagerDashboardService $dashboardService)
    {
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->dashboardService->stats($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Dashboard stats retrieved successfully.',
            'data' => $stats,
        ]);
    }
}
