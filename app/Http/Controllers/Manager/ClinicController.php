<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Clinic\UpdateSettingsRequest;
use App\Http\Resources\Manager\Clinic\ClinicSettingsResource;
use App\Services\Manager\ManagerClinicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function __construct(private readonly ManagerClinicService $clinicService)
    {
    }

    /**
     * Update clinic settings (Manager only)
     */
    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $clinic = $this->clinicService->update(
            $request->user(),
            $request->validatedData(),
            $request->file('logo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Clinic settings updated successfully.',
            'data' => new ClinicSettingsResource($clinic),
        ], 200);
    }

    /**
     * Get current clinic settings (Manager only)
     */
    public function getSettings(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Clinic settings retrieved successfully.',
            'data' => new ClinicSettingsResource(
                $this->clinicService->getClinic($request->user())
            ),
        ], 200);
    }

    /**
     * Get clinic logo (Manager only)
     */
    public function getLogo(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Clinic logo retrieved successfully.',
            'data' => $this->clinicService->logo($request->user()),
        ], 200);
    }
}
