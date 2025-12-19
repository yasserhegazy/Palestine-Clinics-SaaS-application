<?php

namespace App\Http\Requests\Clinic\Report;

use Illuminate\Foundation\Http\FormRequest;

class DailyFinancialRequest extends FormRequest
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

    public function dateValue(): string
    {
        return $this->validated()['date'] ?? today()->format('Y-m-d');
    }
}
