<?php

namespace App\Http\Requests\Clinic\Patient;

use Illuminate\Foundation\Http\FormRequest;

class PatientSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3'],
        ];
    }

    public function searchQuery(): string
    {
        return $this->validated('query');
    }
}
