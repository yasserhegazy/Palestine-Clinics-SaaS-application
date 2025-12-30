<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure API rate limiting
        $this->configureRateLimiting();

        // Domain events -> notifications
        // Note: Removed manual Event::listen() calls because Laravel 11 auto-discovers
        // listeners based on type-hints in their handle() methods. Manual registration
        // would cause duplicate listener execution.

        // To prevent lazy loading issues, ensure related models are eager loaded
        Model::preventLazyLoading();
        Model::automaticallyEagerLoadRelationships();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // General API rate limit (20 requests per minute per user)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->user_id ?: $request->ip());
        });

        // Stricter limit for appointment creation (10 per minute)
        RateLimiter::for('appointments', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->user_id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many appointment requests. Please wait a moment.',
                        'message_ar' => 'طلبات كثيرة جداً. يرجى الانتظار قليلاً.',
                    ], 429, $headers);
                });
        });

        // Payment creation rate limit (15 per minute)
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->user_id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many payment requests. Please wait a moment.',
                        'message_ar' => 'طلبات دفع كثيرة جداً. يرجى الانتظار قليلاً.',
                    ], 429, $headers);
                });
        });

        // Login rate limit (5 attempts per minute)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->input('email') . '|' . $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many login attempts. Please try again later.',
                        'message_ar' => 'محاولات تسجيل دخول كثيرة جداً. يرجى المحاولة لاحقاً.',
                    ], 429, $headers);
                });
        });

        // Patient creation rate limit (20 per minute)
        RateLimiter::for('patients', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->user_id ?: $request->ip());
        });

        // Staff management rate limit (5 per minute - adding staff is sensitive)
        RateLimiter::for('staff', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->user_id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many staff management requests. Please wait.',
                        'message_ar' => 'طلبات إدارة الموظفين كثيرة جداً. يرجى الانتظار.',
                    ], 429, $headers);
                });
        });

        // Clinic registration rate limit (3 per hour - very sensitive)
        RateLimiter::for('registration', function (Request $request) {
            return Limit::perHour(3)->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many registration attempts. Please try again later.',
                        'message_ar' => 'محاولات تسجيل كثيرة جداً. يرجى المحاولة لاحقاً.',
                    ], 429, $headers);
                });
        });

        // Medical records rate limit (15 per minute)
        RateLimiter::for('medical-records', function (Request $request) {
            return Limit::perMinute(15)->by($request->user()?->user_id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many medical record requests. Please wait.',
                        'message_ar' => 'طلبات السجلات الطبية كثيرة جداً. يرجى الانتظار.',
                    ], 429, $headers);
                });
        });

        // Doctor appointment actions (approve/reject/reschedule) - 20 per minute
        RateLimiter::for('doctor-actions', function (Request $request) {
            return Limit::perMinute(20)->by($request->user()?->user_id ?: $request->ip());
        });

        // Secretary appointment actions (approve/reject/reschedule) - 30 per minute
        RateLimiter::for('secretary-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->user_id ?: $request->ip());
        });

        // Admin actions rate limit (10 per minute)
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->user_id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many admin requests. Please wait.',
                        'message_ar' => 'طلبات المسؤول كثيرة جداً. يرجى الانتظار.',
                    ], 429, $headers);
                });
        });

        // Settings update rate limit (5 per minute - prevent spam updates)
        RateLimiter::for('settings', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->user_id ?: $request->ip());
        });

        // File upload rate limit (10 per minute)
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->user_id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'message' => 'Too many upload requests. Please wait.',
                        'message_ar' => 'طلبات رفع الملفات كثيرة جداً. يرجى الانتظار.',
                    ], 429, $headers);
                });
        });
    }
}
