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
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
                    ->where('status', 'Active')
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
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
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['message' => 'Successfully logged out']);
    }
}
