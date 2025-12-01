<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for the authenticated patient
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can view dashboard statistics',
            ], 403);
        }

        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        // 1. Upcoming Appointments Count
        $upcomingAppointmentsCount = Appointment::where('patient_id', $patient->patient_id)
            ->where('appointment_date', '>', now())
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->count();

        // 2. Medical Records Count
        $medicalRecordsCount = MedicalRecord::where('patient_id', $patient->patient_id)
            ->count();

        // 3. Prescriptions Count (Active/Recent)
        // We count medical records that have a non-empty prescription field
        // Ideally, we might filter by date (e.g., last 30 days) to consider them "active"
        // For now, we'll count all records with prescriptions as requested
        $prescriptionsCount = MedicalRecord::where('patient_id', $patient->patient_id)
            ->whereNotNull('prescription')
            ->where('prescription', '!=', '')
            ->count();

        // Get recent prescriptions for the dashboard list (limit 5)
        $recentPrescriptions = MedicalRecord::where('patient_id', $patient->patient_id)
            ->whereNotNull('prescription')
            ->where('prescription', '!=', '')
            ->with(['doctor.user'])
            ->orderBy('visit_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($record) {
                return [
                    'id' => $record->record_id,
                    'name' => 'Prescription from ' . $record->visit_date->format('Y-m-d'), // Placeholder name
                    'doctor' => $record->doctor->user->name,
                    'issuedAt' => $record->visit_date->format('Y-m-d'),
                    'active' => $record->visit_date->diffInDays(now()) < 30, // Example logic for active
                    'details' => $record->prescription
                ];
            });

        return response()->json([
            'stats' => [
                'upcoming_appointments' => $upcomingAppointmentsCount,
                'medical_records' => $medicalRecordsCount,
                'prescriptions' => $prescriptionsCount,
            ],
            'recent_prescriptions' => $recentPrescriptions
        ], 200);
    }
    /**
     * Get authenticated patient's medical history
     */
    public function history(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can view medical history',
            ], 403);
        }

        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        $medicalRecords = MedicalRecord::where('patient_id', $patient->patient_id)
            ->with(['doctor.user', 'patient.user.clinic'])
            ->orderBy('visit_date', 'desc')
            ->get();

        $history = $medicalRecords->map(function ($record) {
            return [
                'date' => $record->visit_date ? $record->visit_date->format('Y-m-d') : 'N/A',
                'clinic' => data_get($record, 'patient.user.clinic.name', 'General Clinic'),
                'diagnosis' => $record->diagnosis ?? 'No diagnosis',
                'doctor' => data_get($record, 'doctor.user.name', 'Unknown Doctor'),
            ];
        });

        return response()->json([
            'data' => $history,
            'count' => $history->count(),
        ]);
    }
}
