<?php

namespace App\Http\Requests\Clinic\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        $patientId = (int) $this->route('patient_id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'nationalId' => [
                'required',
                'string',
                'max:20',
                Rule::unique('patients', 'national_id')->ignore($patientId, 'patient_id'),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'dateOfBirth' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', 'in:Male,Female,Other'],
            'address' => ['required', 'string', 'max:255'],
            'bloodType' => ['nullable', 'string', 'max:5'],
            'allergies' => ['nullable', 'string'],
        ];
    }

    public function patientData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'national_id' => $data['nationalId'],
            'phone' => $data['phone'],
            'date_of_birth' => $data['dateOfBirth'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'blood_type' => $data['bloodType'] ?? null,
            'allergies' => $data['allergies'] ?? null,
        ];
    }
}
