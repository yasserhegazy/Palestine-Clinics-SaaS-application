<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\ClinicRegistrationController;
use App\Http\Controllers\Clinic\StaffController;
use App\Http\Controllers\Clinic\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Handle preflight OPTIONS requests for all routes
Route::options('{any}', function (Request $request) {
    return response('', 200);
})->where('any', '.*');

// Unified authentication routes (single login endpoint for all users).
Route::post('/auth/login', [AuthController::class, 'login']);

// Public clinic registration (no auth required)
Route::post('/register/clinic', [ClinicRegistrationController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});

// Manager and Secretary routes (clinic-specific)
Route::middleware(['auth:sanctum', 'role:Manager,Secretary'])->prefix('clinic')->group(function () {
    // Patient management
    Route::post('/patients', [PatientController::class, 'createPatient']);
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/{patient_id}', [PatientController::class, 'show']);
    Route::get('/patients/search', [PatientController::class, 'search']);
});

// Manager-only routes
Route::middleware(['auth:sanctum', 'role:Manager'])->prefix('clinic')->group(function () {
    // Update own clinic logo
    Route::post('/logo', [ClinicRegistrationController::class, 'updateOwnClinicLogo']);

    // Staff management
    Route::post('/secretaries', [StaffController::class, 'addSecretary']);
    Route::post('/doctors', [StaffController::class, 'addDoctor']);
});

Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin')->group(function () {
    // To see the admin dashboard
    Route::get('/dashboard', function () {
        return response()->json([
            'message' => 'Platform Admin Dashboard',
            'description' => 'Manage all clinics, users, and system settings',
        ]);
    });
    // Manage all clinics, and see their details
    Route::get('/clinics', function () {
        return response()->json(['message' => 'List all clinics']);
    });
    // To see some clinic details
    Route::get('/clinics/{clinic_id}', function ($clinic_id) {
        return response()->json(['message' => "View clinic"]);
    });

    // Update clinic logo
    Route::post('/clinics/{clinic_id}/logo', [ClinicRegistrationController::class, 'updateLogo']);
});
