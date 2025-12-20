<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\Secretary\SecretaryDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected SecretaryDashboardService $dashboardService;

    public function __construct(SecretaryDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get dashboard statistics for secretary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        $stats = $this->dashboardService->getStats($clinicId);

        return response()->json($stats);
    }

    /**
     * Get today's appointments for secretary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function todayAppointments(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        $appointments = $this->dashboardService->getTodayAppointments($clinicId);

        return response()->json($appointments);
    }

    /**
     * Get waiting room data for secretary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function waitingRoom(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        $waitingRoom = $this->dashboardService->getWaitingRoom($clinicId);

        return response()->json($waitingRoom);
    }
}
