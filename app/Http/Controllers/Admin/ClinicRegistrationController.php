<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clinic\RegisterClinicRequest;
use App\Http\Requests\Admin\Clinic\UpdateClinicLogoRequest;
use App\Http\Resources\Admin\Clinic\ClinicDetailResource;
use App\Services\Admin\AdminClinicService;
use App\Models\Clinic;
use Illuminate\Http\JsonResponse;


class ClinicRegistrationController extends Controller
{
    public function __construct(private readonly AdminClinicService $clinicService)
    {
    }

    /**
     * Register a new clinic with manager account.
     */
    public function register(RegisterClinicRequest $request): JsonResponse
    {
        $result = $this->clinicService->register(
            $request->clinicPayload(),
            $request->managerPayload(),
            $request->file('logo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Clinic registered successfully.',
            'data' => [
                'clinic' => new ClinicDetailResource($result['clinic']),
                'manager' => $result['manager'],
                'token' => $result['token'],
            ],
        ], 201);
    }

    /**
     * Update clinic logo (for existing clinics).
     */
    public function updateLogo(UpdateClinicLogoRequest $request, int $clinic_id): JsonResponse
    {
        $clinic = Clinic::findOrFail($clinic_id);

        $updatedClinic = $this->clinicService->updateLogo($clinic, $request->file('logo'));

        return response()->json([
            'success' => true,
            'message' => 'Logo updated successfully.',
            'data' => new ClinicDetailResource($updatedClinic),
        ]);
    }

    /**
     * Update own clinic logo (for managers).
     */
    public function updateOwnClinicLogo(UpdateClinicLogoRequest $request): JsonResponse
    {
        $user = $request->user();

        // Ensure manager has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $clinic = Clinic::findOrFail($user->clinic_id);

        $updatedClinic = $this->clinicService->updateLogo($clinic, $request->file('logo'));

        return response()->json([
            'success' => true,
            'message' => 'Logo updated successfully.',
            'data' => new ClinicDetailResource($updatedClinic),
        ]);
    }
}
