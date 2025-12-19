<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class MedicalRecordService
{
    /**
     * List medical records based on role.
     */
    public function index(User $actor, int $perPage = 10): LengthAwarePaginator
    {
        if ($actor->role === 'Doctor') {
            $doctor = $this->resolveDoctor($actor);

            return MedicalRecord::where('doctor_id', $doctor->doctor_id)
                ->with(['patient.user', 'doctor.user'])
                ->orderByDesc('visit_date')
                ->paginate($perPage);
        }

        if ($actor->role === 'Patient') {
            $patient = $this->resolvePatient($actor);

            return MedicalRecord::where('patient_id', $patient->patient_id)
                ->with(['doctor.user'])
                ->orderByDesc('visit_date')
                ->paginate($perPage);
        }

        throw new AuthorizationException('Unauthorized access.');
    }

    /**
     * Show single record with authorization.
     */
    public function show(User $actor, int $recordId): MedicalRecord
    {
        $record = MedicalRecord::with(['patient.user', 'doctor.user'])->findOrFail($recordId);

        if ($actor->role === 'Doctor') {
            $doctor = $this->resolveDoctor($actor);
            if ($record->doctor_id !== $doctor->doctor_id) {
                throw new AuthorizationException('You do not have permission to view this record.');
            }
        } elseif ($actor->role === 'Patient') {
            $patient = $this->resolvePatient($actor);
            if ($record->patient_id !== $patient->patient_id) {
                throw new AuthorizationException('You do not have permission to view this record.');
            }
        } else {
            throw new AuthorizationException('Unauthorized access.');
        }

        return $record;
    }

    /**
     * Create medical record (doctor only).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(User $actor, array $data): MedicalRecord
    {
        $doctor = $this->resolveDoctor($actor);
        $patient = $this->resolvePatientForDoctor($doctor, $data['patient_id']);

        $record = MedicalRecord::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctor->doctor_id,
            'visit_date' => $data['visit_date'],
            'symptoms' => $data['symptoms'],
            'diagnosis' => $data['diagnosis'],
            'prescription' => $data['prescription'],
            'next_visit' => $data['next_visit'] ?? null,
        ]);

        return $record->fresh(['patient.user', 'doctor.user']);
    }

    /**
     * Update a medical record (doctor only).
     *
     * @throws AuthorizationException
     */
    public function update(User $actor, int $recordId, array $data): MedicalRecord
    {
        $doctor = $this->resolveDoctor($actor);
        $record = MedicalRecord::findOrFail($recordId);

        if ($record->doctor_id !== $doctor->doctor_id) {
            throw new AuthorizationException('You do not have permission to update this record.');
        }

        $record->update($data);

        return $record->fresh(['patient.user', 'doctor.user']);
    }

    /**
     * Delete a medical record (doctor only).
     *
     * @throws AuthorizationException
     */
    public function delete(User $actor, int $recordId): void
    {
        $doctor = $this->resolveDoctor($actor);
        $record = MedicalRecord::findOrFail($recordId);

        if ($record->doctor_id !== $doctor->doctor_id) {
            throw new AuthorizationException('You do not have permission to delete this record.');
        }

        $record->delete();
    }

    private function resolveDoctor(User $actor): Doctor
    {
        if ($actor->role !== 'Doctor') {
            throw new AuthorizationException('Only doctors can access this resource.');
        }

        $doctor = Doctor::where('user_id', $actor->user_id)->first();

        if (!$doctor) {
            throw new AuthorizationException('Doctor profile not found.');
        }

        return $doctor;
    }

    private function resolvePatient(User $actor)
    {
        $patient = $actor->patient;

        if (!$patient) {
            throw new AuthorizationException('Patient profile not found.');
        }

        return $patient;
    }

    private function resolvePatientForDoctor(Doctor $doctor, int $patientId): Patient
    {
        $patient = Patient::with('user')->findOrFail($patientId);

        if ($patient->user->clinic_id !== $doctor->user->clinic_id) {
            throw new AuthorizationException('Patient does not belong to your clinic.');
        }

        return $patient;
    }
}
