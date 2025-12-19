<?php

namespace App\Http\Requests\Clinic\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'appointment_id' => ['required', 'exists:appointments,appointment_id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:Cash,Later,Partial,Exempt'],
            'notes' => ['nullable', 'string', 'max:500'],
            'exemption_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
