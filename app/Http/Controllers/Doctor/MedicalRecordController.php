<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\MedicalRecord\StoreMedicalRecordRequest;
use App\Http\Requests\Doctor\MedicalRecord\UpdateMedicalRecordRequest;
use App\Http\Resources\MedicalRecord\MedicalRecordResource;
use App\Services\Doctor\MedicalRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function __construct(private readonly MedicalRecordService $medicalRecordService)
    {
    }

    /**
     * List medical records based on user role
     */
    public function index(Request $request): JsonResponse
    {
        $records = $this->medicalRecordService->index($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Medical records retrieved successfully.',
            'data' => [
                'records' => MedicalRecordResource::collection($records),
                'pagination' => [
                    'current_page' => $records->currentPage(),
                    'per_page' => $records->perPage(),
                    'total' => $records->total(),
                    'last_page' => $records->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * View a single medical record
     */
    public function show(Request $request, int $record_id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Medical record retrieved successfully.',
            'data' => new MedicalRecordResource(
                $this->medicalRecordService->show($request->user(), $record_id)
            ),
        ], 200);
    }

    /**
     * Create a new medical record (Doctor only)
     */
    public function store(StoreMedicalRecordRequest $request): JsonResponse
    {
        $record = $this->medicalRecordService->store($request->user(), $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Medical record created successfully.',
            'data' => new MedicalRecordResource($record),
        ], 201);
    }

    /**
     * Update an existing medical record (Doctor only)
     */
    public function update(UpdateMedicalRecordRequest $request, int $record_id): JsonResponse
    {
        $record = $this->medicalRecordService->update(
            $request->user(),
            $record_id,
            $request->payload()
        );

        return response()->json([
            'success' => true,
            'message' => 'Medical record updated successfully.',
            'data' => new MedicalRecordResource($record),
        ], 200);
    }

    /**
     * Delete a medical record (Doctor only)
     */
    public function destroy(Request $request, int $record_id): JsonResponse
    {
        $this->medicalRecordService->delete($request->user(), $record_id);

        return response()->json([
            'success' => true,
            'message' => 'Medical record deleted successfully.',
        ], 200);
    }
}
