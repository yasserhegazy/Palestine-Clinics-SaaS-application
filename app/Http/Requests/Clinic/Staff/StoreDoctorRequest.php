<?php

namespace App\Http\Requests\Clinic\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],
            'specialization' => ['required', 'string', 'max:100'],
            'available_days' => ['required', 'string', 'max:100'],
            'clinic_room' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'slot_duration' => ['required', 'integer', 'min:5', 'max:120'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
