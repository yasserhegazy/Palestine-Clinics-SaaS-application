<?php

namespace App\Http\Requests\Clinic\Report;

use Illuminate\Foundation\Http\FormRequest;

class RevenueAnalyticsPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', 'in:week,month,quarter,year'],
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function period(): string
    {
        return $this->validated()['period'] ?? 'month';
    }

    public function days(): int
    {
        return (int) ($this->validated()['days'] ?? 30);
    }
}
