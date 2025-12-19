<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    public function credentials(): array
    {
        $validated = $this->validated();
        $loginField = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        return [
            'field' => $loginField,
            'value' => $validated['login'],
            'password' => $validated['password'],
        ];
    }
}
