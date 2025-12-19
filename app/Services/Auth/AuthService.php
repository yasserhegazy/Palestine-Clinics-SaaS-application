<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt login and return user with token.
     *
     * @return array{user: User, token: string, is_platform_admin: bool}
     *
     * @throws ValidationException
     */
    public function login(string $field, string $value, string $password): array
    {
        $normalizedValue = $field === 'phone'
            ? $this->normalizePhone($value)
            : $value;

        $user = User::where($field, $normalizedValue)
            ->where('status', 'Active')
            ->first();

        if (!$user || !Hash::check($password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $tokenName = strtolower($user->role) . '-token';
        $token = $user->createToken($tokenName)->plainTextToken;

        if ($user->clinic_id) {
            $user->load('clinic');
        }

        return [
            'user' => $user,
            'token' => $token,
            'is_platform_admin' => $user->role === 'Admin' && is_null($user->clinic_id),
        ];
    }

    public function logout(?User $user): void
    {
        $token = $user?->currentAccessToken();

        if ($token) {
            $token->delete();
        }
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
