<?php

namespace App\Http\Requests\Clinic\Payment;

use Illuminate\Foundation\Http\FormRequest;

class PaymentIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:Paid,Pending,Partial,Exempt,Refunded'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'today' => ['nullable', 'boolean'],
            'patient_id' => ['nullable', 'integer', 'exists:patients,patient_id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
