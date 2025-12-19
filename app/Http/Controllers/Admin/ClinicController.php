<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Clinic\ClinicIndexRequest;
use App\Http\Requests\Admin\Clinic\UpdateClinicRequest;
use App\Http\Requests\Admin\Clinic\UpdateClinicLogoRequest;
use App\Http\Resources\Admin\Clinic\ClinicDetailResource;
use App\Http\Resources\Admin\Clinic\ClinicSummaryResource;
use App\Services\Admin\AdminClinicService;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    public function __construct(private readonly AdminClinicService $clinicService)
    {
    }

    /**
     * Get all clinics with optional filters.
     */
    public function index(ClinicIndexRequest $request): JsonResponse
    {
        $clinics = $this->clinicService->list($request->filters());

        return response()->json([
            'success' => true,
            'message' => 'Clinics retrieved successfully.',
            'data' => [
                'clinics' => ClinicSummaryResource::collection($clinics),
                'pagination' => [
                    'current_page' => $clinics->currentPage(),
                    'per_page' => $clinics->perPage(),
                    'total' => $clinics->total(),
                    'last_page' => $clinics->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get a single clinic by ID with detailed analytics.
     */
    public function show(int $id): JsonResponse
    {
        $clinic = $this->clinicService->show($id);

        return response()->json([
            'success' => true,
            'message' => 'Clinic details retrieved successfully.',
            'data' => new ClinicDetailResource($clinic->load(['users', 'appointments'])),
        ]);
    }

    /**
     * Update clinic details.
     */
    public function update(UpdateClinicRequest $request, int $id): JsonResponse
    {
        $clinic = $this->clinicService->update($id, $request->clinicData());

        return response()->json([
            'success' => true,
            'message' => 'Clinic updated successfully.',
            'data' => new ClinicDetailResource($clinic),
        ]);
    }

    /**
     * Toggle clinic status (Active/Inactive).
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $clinic = $this->clinicService->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => "Clinic {$clinic->status} successfully.",
            'data' => new ClinicDetailResource($clinic),
        ]);
    }

    /**
     * Delete a clinic (soft delete recommended in production).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->clinicService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Clinic deleted successfully.',
        ]);
    }

    public function updateLogo(UpdateClinicLogoRequest $request, int $clinic_id): JsonResponse
    {
        $clinic = $this->clinicService->show($clinic_id);

        $updatedClinic = $this->clinicService->updateLogo($clinic, $request->file('logo'));

        return response()->json([
            'success' => true,
            'message' => 'Logo updated successfully.',
            'data' => new ClinicDetailResource($updatedClinic),
        ]);
    }
}
