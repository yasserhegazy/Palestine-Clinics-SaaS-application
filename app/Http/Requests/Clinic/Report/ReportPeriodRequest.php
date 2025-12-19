<?php

namespace App\Http\Requests\Clinic\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function period(): array
    {
        $validated = $this->validated();

        return [
            'start_date' => $validated['start_date'] ?? today()->startOfMonth()->format('Y-m-d'),
            'end_date' => $validated['end_date'] ?? today()->format('Y-m-d'),
        ];
    }
}
