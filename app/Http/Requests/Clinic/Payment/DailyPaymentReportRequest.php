<?php

namespace App\Http\Requests\Clinic\Payment;

use Illuminate\Foundation\Http\FormRequest;

class DailyPaymentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
        ];
    }

    public function reportDate(): string
    {
        return $this->validated()['date'] ?? today()->format('Y-m-d');
    }
}
