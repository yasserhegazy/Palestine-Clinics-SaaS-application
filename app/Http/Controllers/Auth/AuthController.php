<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Universal login for all user types. Uses RBAC to vary response.
     * Accepts either email or phone number as login identifier.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        // Determine if login is email or phone
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Normalize phone number if needed (add +970 if starts with 0)
        $loginValue = $request->login;
        if ($loginField === 'phone') {
            if (str_starts_with($loginValue, '0')) {
                $loginValue = '+970' . substr($loginValue, 1);
            } else if (!str_starts_with($loginValue, '+')) {
                $loginValue = '+970' . $loginValue;
            }
        }

        $user = User::where($loginField, $loginValue)
                    ->where('status', 'Active')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create Sanctum token and return role-aware payload
        $tokenName = strtolower($user->role) . '-token';
        $token = $user->createToken($tokenName)->plainTextToken;

        // Load clinic only when applicable
        if ($user->clinic_id) {
            $user->load('clinic');
        }

        $response = [
            'user' => $user,
            'token' => $token,
            'role' => $user->role,
        ];

        // Flag platform admin (SaaS admin) when clinic_id is null and role is Admin
        $response['is_platform_admin'] = $user->role === 'Admin' && is_null($user->clinic_id);

        return response()->json($response);
    }

    /**
     * Logout current token
     */
    public function logout(Request $request)
    {
        /** @var \Laravel\Sanctum\PersonalAccessToken|null $token */
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Successfully logged out']);
    }
}
