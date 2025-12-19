<?php

namespace App\Http\Requests\Doctor\MedicalRecord;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMedicalRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Doctor';
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['sometimes', 'date'],
            'symptoms' => ['sometimes', 'string'],
            'diagnosis' => ['sometimes', 'string'],
            'prescription' => ['sometimes', 'string'],
            'next_visit' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
