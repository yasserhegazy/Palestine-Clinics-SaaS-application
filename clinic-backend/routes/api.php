<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\ClinicRegistrationController;
use App\Http\Controllers\Clinic\StaffController;
use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Doctor\AppointmentRequestsController;
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

// Patient routes
Route::middleware(['auth:sanctum', 'role:Patient'])->prefix('patient')->group(function () {
    // Appointment management
    Route::post('/appointments', [\App\Http\Controllers\Patient\AppointmentController::class, 'createAppointment']);
    Route::get('/appointments', [\App\Http\Controllers\Patient\AppointmentController::class, 'index']);
    Route::get('/appointments/upcoming', [\App\Http\Controllers\Patient\AppointmentController::class, 'upcoming']);
    Route::get('/appointments/{appointment_id}', [\App\Http\Controllers\Patient\AppointmentController::class, 'show']);
    Route::post('/appointments/{appointment_id}/cancel', [\App\Http\Controllers\Patient\AppointmentController::class, 'cancel']);
    
    // Get available doctors
    Route::get('/doctors', [\App\Http\Controllers\Patient\AppointmentController::class, 'getAvailableDoctors']);
});

// Manager and Secretary routes (clinic-specific)
Route::middleware(['auth:sanctum', 'role:Manager,Secretary'])->prefix('clinic')->group(function () {
    // Patient management
    Route::post('/patients', [PatientController::class, 'createPatient']);
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/search', [PatientController::class, 'search']);
    Route::get('/patients/lookup', [PatientController::class, 'searchByIdentifier']);
    Route::get('/patients/{patient_id}', [PatientController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:Doctor'])->prefix('doctor')->group(function () {
    // Appointment requests
    Route::get('/appointments', [AppointmentRequestsController::class, 'index']);
    Route::put('/appointments/approve/{appointment_id}', [AppointmentRequestsController::class, 'approve']);
});
// Manager-only routes
Route::middleware(['auth:sanctum', 'role:Manager'])->prefix('clinic')->group(function () {
    // Update own clinic logo
    Route::post('/logo', [ClinicRegistrationController::class, 'updateOwnClinicLogo']);

    // Staff management
    Route::post('/secretaries', [StaffController::class, 'addSecretary']);
    Route::post('/doctors', [StaffController::class, 'addDoctor']);
    Route::put('/staff/{user_id}', [StaffController::class, 'update_member']);
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
