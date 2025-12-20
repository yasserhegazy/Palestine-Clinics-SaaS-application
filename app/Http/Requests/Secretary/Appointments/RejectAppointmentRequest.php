<?php

namespace App\Http\Requests\Secretary\Appointments;

use Illuminate\Foundation\Http\FormRequest;

class RejectAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Secretary';
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function reason(): string
    {
        return $this->validated()['rejection_reason'];
    }
}
