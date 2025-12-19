<?php

namespace App\Http\Requests\Clinic\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        $userId = (int) $this->route('user_id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId, 'user_id')],
            'phone' => ['required', 'string', 'max:20'],
            'specialization' => ['sometimes', 'string', 'max:100'],
            'available_days' => ['sometimes', 'string', 'max:100'],
            'clinic_room' => ['sometimes', 'string', 'max:50'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'slot_duration' => ['sometimes', 'integer', 'min:5', 'max:120'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
