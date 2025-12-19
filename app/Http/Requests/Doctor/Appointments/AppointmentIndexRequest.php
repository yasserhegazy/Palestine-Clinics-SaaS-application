<?php

namespace App\Http\Requests\Doctor\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Doctor';
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
