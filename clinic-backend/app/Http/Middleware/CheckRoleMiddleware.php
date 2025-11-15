<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if(!$request->user()){
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => 'You must be logged in to access this resources.'
            ], 401);
        }

        $user = $request->user();

        // To check if the user's role is in the allowed roles
        if(!in_array($user->role, $roles)){
            return response()->json([
                'message' => 'Forbidden',
                'error' => "You don't have the permission to access this resource",
                'required_role' => count($roles) === 1 ? $roles[0] : $roles,
                'your_role' => $user->role,
            ], 403);
        }
        
        return $next($request);
    }
}
