<?php

namespace App\Http\Requests\Admin\Clinic;

use Illuminate\Foundation\Http\FormRequest;

class RegisterClinicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clinic.name' => ['required', 'string', 'max:100'],
            'clinic.speciality' => ['nullable', 'string', 'max:100'],
            'clinic.address' => ['required', 'string', 'max:255'],
            'clinic.phone' => ['required', 'string', 'max:20'],
            'clinic.email' => ['required', 'email', 'max:100', 'unique:clinics,email'],
            'clinic.subscription_plan' => ['required', 'in:Basic,Standard,Premium'],
            'manager.name' => ['required', 'string', 'max:100'],
            'manager.email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'manager.phone' => ['required', 'string', 'max:20'],
            'manager.password' => ['required', 'string', 'min:8', 'confirmed'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }

    public function clinicPayload(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['clinic']['name'],
            'speciality' => $validated['clinic']['speciality'] ?? null,
            'address' => $validated['clinic']['address'],
            'phone' => $this->normalizePhone($validated['clinic']['phone']),
            'email' => $validated['clinic']['email'],
            'subscription_plan' => $validated['clinic']['subscription_plan'],
            'status' => 'Active',
        ];
    }

    public function managerPayload(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['manager']['name'],
            'email' => $validated['manager']['email'],
            'phone' => $this->normalizePhone($validated['manager']['phone']),
            'password_hash' => bcrypt($validated['manager']['password']),
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+970' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '+')) {
            return '+970' . $phone;
        }

        return $phone;
    }
}

