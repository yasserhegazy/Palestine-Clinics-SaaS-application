<?php

namespace App\Http\Requests\Admin\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'Admin';
    }

    public function rules(): array
    {
        $clinicId = (int) $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255'],
            'address' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:255', "unique:clinics,email,{$clinicId},clinic_id"],
            'subscription_plan' => ['sometimes', 'in:Basic,Standard,Premium'],
            'subscription_start' => ['sometimes', 'date'],
            'subscription_end' => ['sometimes', 'date', 'after:subscription_start'],
        ];
    }

    public function clinicData(): array
    {
        return $this->validated();
    }
}
