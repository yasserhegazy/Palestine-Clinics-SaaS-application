<?php

namespace App\Http\Requests\Patient\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Patient';
    }

    public function rules(): array
    {
        return [
            'doctor_id' => ['sometimes', 'integer', 'exists:doctors,doctor_id'],
            'appointment_date' => ['sometimes', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
