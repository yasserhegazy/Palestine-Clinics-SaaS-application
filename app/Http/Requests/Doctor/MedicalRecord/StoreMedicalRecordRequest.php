<?php

namespace App\Http\Requests\Doctor\MedicalRecord;

use Illuminate\Foundation\Http\FormRequest;

class StoreMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Doctor';
    }

    public function rules(): array
    {
        return [
            'patient_id' => ['required', 'exists:patients,patient_id'],
            'visit_date' => ['required', 'date'],
            'symptoms' => ['required', 'string'],
            'diagnosis' => ['required', 'string'],
            'prescription' => ['required', 'string'],
            'next_visit' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
