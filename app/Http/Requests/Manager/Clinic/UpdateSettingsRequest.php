<?php

namespace App\Http\Requests\Manager\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Manager';
    }

    public function rules(): array
    {
        $clinicId = $this->user()?->clinic_id ?? 0;

        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'address' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:100', "unique:clinics,email,{$clinicId},clinic_id"],
            'subscription_plan' => ['sometimes', 'in:Basic,Standard,Premium'],
            'status' => ['sometimes', 'in:Active,Inactive'],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,gif,svg', 'max:2048'],
        ];
    }

    /**
     * Return validated payload for updating clinic settings.
     */
    public function validatedData(): array
    {
        return $this->validated();
    }
}
