<?php

namespace App\Http\Requests\Clinic\Patient;

use Illuminate\Foundation\Http\FormRequest;

class PatientIdentifierSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'min:3', 'max:25'],
        ];
    }

    public function identifier(): string
    {
        return trim($this->validated('identifier'));
    }
}
