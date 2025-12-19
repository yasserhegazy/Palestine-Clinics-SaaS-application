<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Patient\PatientIdentifierSearchRequest;
use App\Http\Requests\Clinic\Patient\PatientSearchRequest;
use App\Http\Requests\Clinic\Patient\StorePatientRequest;
use App\Http\Requests\Clinic\Patient\UpdatePatientRequest;
use App\Http\Resources\MedicalRecord\MedicalRecordResource;
use App\Http\Resources\Patient\PatientResource;
use App\Http\Resources\Patient\PatientSummaryResource;
use App\Services\Clinic\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    public function __construct(private readonly PatientService $patientService)
    {
    }

    /**
     * Create a new patient and user account.
     */
    public function createPatient(StorePatientRequest $request): JsonResponse
    {
        Log::info('Patient creation request', ['data' => $request->all()]);

        $result = $this->patientService->create($request->user(), $request->patientData());

        return response()->json([
            'success' => true,
            'message' => 'Patient created successfully.',
            'data' => [
                'patient' => new PatientResource($result['patient']),
                'temporary_password' => $result['temporary_password'],
                'sms_sent' => false,
            ],
        ], 201);
    }

    /**
     * Get all patients for the current clinic.
     */
    public function index(Request $request): JsonResponse
    {
        $patients = $this->patientService->paginate($request->user(), 20);

        return response()->json([
            'success' => true,
            'message' => 'Patients retrieved successfully.',
            'data' => [
                'patients' => PatientSummaryResource::collection($patients),
                'pagination' => [
                    'current_page' => $patients->currentPage(),
                    'per_page' => $patients->perPage(),
                    'total' => $patients->total(),
                    'last_page' => $patients->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get a specific patient.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $patient = $this->patientService->show($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'Patient retrieved successfully.',
            'data' => new PatientResource($patient),
        ]);
    }

    public function updatePatient(UpdatePatientRequest $request, int $patient_id): JsonResponse
    {
        $patient = $this->patientService->update($request->user(), $patient_id, $request->patientData());

        return response()->json([
            'success' => true,
            'message' => 'Patient updated successfully.',
            'data' => new PatientResource($patient),
        ]);
    }

    /**
     * Search for patients by national ID or phone.
     */
    public function search(PatientSearchRequest $request): JsonResponse
    {
        $patients = $this->patientService->search($request->user(), $request->searchQuery());

        return response()->json([
            'success' => true,
            'message' => 'Patients search completed successfully.',
            'data' => PatientSummaryResource::collection($patients),
        ]);
    }

    /**
     * Search for patients using a single identifier field (national ID or phone prefix).
     */
    public function searchByIdentifier(PatientIdentifierSearchRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Patients lookup completed successfully.',
            'data' => PatientSummaryResource::collection(
                $this->patientService->searchByIdentifier(
                    $request->user(),
                    $request->identifier()
                )
            ),
        ]);
    }

    /**
     * Get patient medical history (previous visits).
     */
    public function history(Request $request, int $id): JsonResponse
    {
        // Log for debugging
        Log::info("Fetching medical history for patient_id: {$id}");

        $history = $this->patientService->history($request->user(), $id);

        Log::info("Found {$history['medical_records']->count()} medical records for patient {$id}");

        return response()->json([
            'success' => true,
            'message' => 'Patient medical history retrieved successfully.',
            'data' => [
                'patient' => new PatientResource($history['patient']),
                'medical_history' => MedicalRecordResource::collection(
                    $history['medical_records']->loadMissing(['doctor.user', 'patient.user'])
                ),
            ],
        ]);
    }
}
