<?php

namespace App\Http\Requests\Clinic\Payment;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:Cash,Later,Partial,Exempt'],
            'status' => ['nullable', 'in:Paid,Pending,Partial,Exempt,Refunded'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
