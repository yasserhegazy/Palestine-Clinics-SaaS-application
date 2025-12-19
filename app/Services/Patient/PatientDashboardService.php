<?php

namespace App\Services\Patient;

use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class PatientDashboardService
{
    /**
     * @throws AuthorizationException
     */
    public function stats(User $actor): array
    {
        $patient = $this->resolvePatient($actor);

        $upcomingAppointmentsCount = Appointment::where('patient_id', $patient->patient_id)
            ->where('appointment_date', '>', now())
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->count();

        $medicalRecordsCount = MedicalRecord::where('patient_id', $patient->patient_id)->count();

        $prescriptionsCount = MedicalRecord::where('patient_id', $patient->patient_id)
            ->whereNotNull('prescription')
            ->where('prescription', '!=', '')
            ->count();

        $recentPrescriptions = MedicalRecord::where('patient_id', $patient->patient_id)
            ->whereNotNull('prescription')
            ->where('prescription', '!=', '')
            ->with(['doctor.user'])
            ->orderByDesc('visit_date')
            ->limit(5)
            ->get()
            ->map(function (MedicalRecord $record) {
                return [
                    'id' => $record->record_id,
                    'name' => 'Prescription from ' . optional($record->visit_date)->format('Y-m-d'),
                    'doctor' => $record->doctor?->user?->name,
                    'issuedAt' => optional($record->visit_date)->format('Y-m-d'),
                    'active' => $record->visit_date?->diffInDays(now()) < 30,
                    'details' => $record->prescription,
                ];
            });

        return [
            'stats' => [
                'upcoming_appointments' => $upcomingAppointmentsCount,
                'medical_records' => $medicalRecordsCount,
                'prescriptions' => $prescriptionsCount,
            ],
            'recent_prescriptions' => $recentPrescriptions,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     *
     * @throws AuthorizationException
     */
    public function history(User $actor): Collection
    {
        $patient = $this->resolvePatient($actor);

        return MedicalRecord::where('patient_id', $patient->patient_id)
            ->with(['doctor.user', 'patient.user.clinic'])
            ->orderByDesc('visit_date')
            ->get()
            ->map(function (MedicalRecord $record) {
                return [
                    'date' => optional($record->visit_date)->format('Y-m-d') ?? 'N/A',
                    'clinic' => data_get($record, 'patient.user.clinic.name', 'General Clinic'),
                    'diagnosis' => $record->diagnosis ?? 'No diagnosis',
                    'doctor' => data_get($record, 'doctor.user.name', 'Unknown Doctor'),
                ];
            });
    }

    /**
     * @throws AuthorizationException
     */
    private function resolvePatient(User $actor): Patient
    {
        if ($actor->role !== 'Patient') {
            throw new AuthorizationException('Only patients can access this resource.');
        }

        $patient = Patient::where('user_id', $actor->user_id)->first();

        if (!$patient) {
            throw new AuthorizationException('Patient record not found.');
        }

        return $patient;
    }
}
