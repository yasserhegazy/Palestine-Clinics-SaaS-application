<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * Universal login for all user types. Uses RBAC to vary response.
     * Accepts either email or phone number as login identifier.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->credentials();

        $result = $this->authService->login(
            $credentials['field'],
            $credentials['value'],
            $credentials['password']
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'role' => $result['user']->role,
                'is_platform_admin' => $result['is_platform_admin'],
            ],
        ]);
    }

    /**
     * Logout current token
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out.',
        ]);
    }
}
