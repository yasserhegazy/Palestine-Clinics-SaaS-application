<?php

namespace App\Http\Requests\Clinic\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class AvailableTimeSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    public function requestedDate(): string
    {
        return $this->validated('date');
    }
}
