<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, [
                'Access-Control-Allow-Origin' => $this->getAllowedOrigins($request),
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS, PATCH',
                'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400'
            ]);
        }

        $response = $next($request);

        // Add CORS headers to actual requests
        $response->headers->set('Access-Control-Allow-Origin', $this->getAllowedOrigins($request));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');  
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN');
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }

    /**
     * Get allowed origins based on environment
     */
    private function getAllowedOrigins(Request $request): string
    {
        $allowedOrigins = [
            'http://localhost:3000',      // React development
            'http://localhost:3001',      // Next.js development  
            'http://127.0.0.1:3000',     // React development alternative
            'http://127.0.0.1:3001',     // Next.js development alternative
            'https://localhost:3000',     // HTTPS React development
            'https://localhost:3001',     // HTTPS Next.js development
        ];

        // Add production domains from environment
        $prodOrigins = env('CORS_ALLOWED_ORIGINS', '');
        if ($prodOrigins) {
            $allowedOrigins = array_merge($allowedOrigins, explode(',', $prodOrigins));
        }

        $origin = $request->header('Origin');
        
        if (in_array($origin, $allowedOrigins)) {
            return $origin;
        }

        // For development, allow all localhost origins
        if (app()->environment('local') && $origin) {
            if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/', $origin)) {
                return $origin;
            }
        }

        return $allowedOrigins[0]; // Default to first allowed origin
    }
}
