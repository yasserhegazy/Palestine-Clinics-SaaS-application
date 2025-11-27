<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicalRecordController extends Controller
{
    /**
     * List medical records based on user role
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'Doctor') {
            // Get doctor record
            $doctor = Doctor::where('user_id', $user->user_id)->first();
            
            if (!$doctor) {
                return response()->json([
                    'message' => 'Doctor profile not found',
                ], 404);
            }

            // Get all medical records created by this doctor
            $records = MedicalRecord::where('doctor_id', $doctor->doctor_id)
                ->with(['patient.user', 'doctor.user'])
                ->orderBy('visit_date', 'desc')
                ->paginate(10);

            return response()->json($records, 200);

        } elseif ($user->role === 'Patient') {
            // Get patient record
            $patient = Patient::where('user_id', $user->user_id)->first();
            
            if (!$patient) {
                return response()->json([
                    'message' => 'Patient profile not found',
                ], 404);
            }

            // Get all medical records for this patient
            $records = MedicalRecord::where('patient_id', $patient->patient_id)
                ->with(['doctor.user'])
                ->orderBy('visit_date', 'desc')
                ->paginate(10);

            return response()->json($records, 200);

        } else {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }
    }

    /**
     * View a single medical record
     */
    public function show(Request $request, $record_id)
    {
        $user = $request->user();
        $record = MedicalRecord::with(['patient.user', 'doctor.user'])->find($record_id);

        if (!$record) {
            return response()->json([
                'message' => 'Medical record not found',
            ], 404);
        }

        if ($user->role === 'Doctor') {
            $doctor = Doctor::where('user_id', $user->user_id)->first();
            
            if (!$doctor) {
                return response()->json([
                    'message' => 'Doctor profile not found',
                ], 404);
            }

            // Doctor can only view records they created
            if ($record->doctor_id !== $doctor->doctor_id) {
                return response()->json([
                    'message' => 'You do not have permission to view this record',
                ], 403);
            }

        } elseif ($user->role === 'Patient') {
            $patient = Patient::where('user_id', $user->user_id)->first();
            
            if (!$patient) {
                return response()->json([
                    'message' => 'Patient profile not found',
                ], 404);
            }

            // Patient can only view their own records
            if ($record->patient_id !== $patient->patient_id) {
                return response()->json([
                    'message' => 'You do not have permission to view this record',
                ], 403);
            }

        } else {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
        }

        return response()->json([
            'record' => $record,
        ], 200);
    }

    /**
     * Create a new medical record (Doctor only)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can create medical records',
            ], 403);
        }

        $doctor = Doctor::where('user_id', $user->user_id)->first();
        
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,patient_id',
            'visit_date' => 'required|date',
            'symptoms' => 'required|string',
            'diagnosis' => 'required|string',
            'prescription' => 'required|string',
            'next_visit' => 'nullable|date|after:today',
        ]);

        // Verify patient belongs to the same clinic
        $patient = Patient::with('user')->find($validated['patient_id']);
        
        if ($patient->user->clinic_id !== $user->clinic_id) {
            return response()->json([
                'message' => 'Patient does not belong to your clinic',
            ], 403);
        }

        DB::beginTransaction();

        try {
            $record = MedicalRecord::create([
                'patient_id' => $validated['patient_id'],
                'doctor_id' => $doctor->doctor_id,
                'visit_date' => $validated['visit_date'],
                'symptoms' => $validated['symptoms'],
                'diagnosis' => $validated['diagnosis'],
                'prescription' => $validated['prescription'],
                'next_visit' => $validated['next_visit'] ?? null,
            ]);

            DB::commit();

            // Load relationships
            $record->load(['patient.user', 'doctor.user']);

            return response()->json([
                'message' => 'Medical record created successfully',
                'record' => $record,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create medical record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing medical record (Doctor only)
     */
    public function update(Request $request, $record_id)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can update medical records',
            ], 403);
        }

        $doctor = Doctor::where('user_id', $user->user_id)->first();
        
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        $record = MedicalRecord::find($record_id);

        if (!$record) {
            return response()->json([
                'message' => 'Medical record not found',
            ], 404);
        }

        // Doctor can only update records they created
        if ($record->doctor_id !== $doctor->doctor_id) {
            return response()->json([
                'message' => 'You do not have permission to update this record',
            ], 403);
        }

        $validated = $request->validate([
            'visit_date' => 'sometimes|date',
            'symptoms' => 'sometimes|string',
            'diagnosis' => 'sometimes|string',
            'prescription' => 'sometimes|string',
            'next_visit' => 'nullable|date|after:today',
        ]);

        DB::beginTransaction();

        try {
            $record->update($validated);

            DB::commit();

            // Reload relationships
            $record->load(['patient.user', 'doctor.user']);

            return response()->json([
                'message' => 'Medical record updated successfully',
                'record' => $record,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update medical record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a medical record (Doctor only)
     */
    public function destroy(Request $request, $record_id)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can delete medical records',
            ], 403);
        }

        $doctor = Doctor::where('user_id', $user->user_id)->first();
        
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        $record = MedicalRecord::find($record_id);

        if (!$record) {
            return response()->json([
                'message' => 'Medical record not found',
            ], 404);
        }

        // Doctor can only delete records they created
        if ($record->doctor_id !== $doctor->doctor_id) {
            return response()->json([
                'message' => 'You do not have permission to delete this record',
            ], 403);
        }

        $record->delete();

        return response()->json([
            'message' => 'Medical record deleted successfully',
        ], 200);
    }
}
