<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Staff\StoreDoctorRequest;
use App\Http\Requests\Clinic\Staff\StoreSecretaryRequest;
use App\Http\Requests\Clinic\Staff\UpdateStaffRequest;
use App\Http\Resources\Staff\StaffResource;
use App\Services\Clinic\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __construct(private readonly StaffService $staffService)
    {
    }

    /**
     * Add a new secretary to the clinic.
     */
    public function addSecretary(StoreSecretaryRequest $request): JsonResponse
    {
        $result = $this->staffService->addSecretary($request->user(), $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Secretary added successfully.',
            'data' => [
                'secretary' => new StaffResource($result['secretary']),
                'temporary_password' => $result['temporary_password'],
            ],
        ], 201);
    }

    /**
     * Add a new doctor to the clinic.
     */
    public function addDoctor(StoreDoctorRequest $request): JsonResponse
    {
        $result = $this->staffService->addDoctor($request->user(), $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Doctor added successfully.',
            'data' => [
                'doctor' => new StaffResource($result['doctor']->user),
                'temporary_password' => $result['temporary_password'],
            ],
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Staff members retrieved successfully.',
            'data' => StaffResource::collection(
                $this->staffService->list($request->user())
            ),
        ], 200);
    }

    public function update_member(UpdateStaffRequest $request, int $user_id): JsonResponse
    {
        $member = $this->staffService->update($request->user(), $user_id, $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Staff member updated successfully.',
            'data' => new StaffResource($member),
        ], 200);
    }

    public function delete_member(Request $request, int $user_id): JsonResponse
    {
        $this->staffService->delete($request->user(), $user_id);

        return response()->json([
            'success' => true,
            'message' => 'Staff member deleted successfully.',
        ], 200);
    }
}
