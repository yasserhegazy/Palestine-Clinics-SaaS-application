<?php

namespace App\Http\Requests\Doctor\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Doctor';
    }

    public function rules(): array
    {
        return [
            'visit_date' => ['nullable', 'date'],
            'symptoms' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'prescription' => ['nullable', 'string'],
            'next_visit' => ['nullable', 'date', 'after:today'],
            'create_next_appointment' => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
