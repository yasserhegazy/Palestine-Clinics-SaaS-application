<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\ClinicRegistrationController;
use App\Http\Controllers\Admin\ClinicController as AdminClinicController;
use App\Http\Controllers\Clinic\AppointmentController;
use App\Http\Controllers\Clinic\StaffController;
use App\Http\Controllers\Clinic\PatientController;
use App\Http\Controllers\Doctor\AppointmentRequestsController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Manager\ClinicController as ManagerClinicController;
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

    // Medical Records (read-only for patients)
    Route::get('/medical-records', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'index']);
    Route::get('/medical-records/{record_id}', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'show']);

    // Dashboard Stats
    Route::get('/dashboard/stats', [\App\Http\Controllers\Patient\DashboardController::class, 'stats']);

    // Medical History
    Route::get('/medical-history', [\App\Http\Controllers\Patient\DashboardController::class, 'history']);
});

// Manager and Secretary routes (clinic-specific)
Route::middleware(['auth:sanctum', 'role:Manager,Secretary,Doctor'])->prefix('clinic')->group(function () {
    // Patient management
    Route::post('/patients', [PatientController::class, 'createPatient']);
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/search', [PatientController::class, 'search']);
    Route::get('/patients/lookup', [PatientController::class, 'searchByIdentifier']);
    Route::get('/patients/{patient_id}', [PatientController::class, 'show']);
    Route::post('/appointments/create', [AppointmentController::class, 'createAppointmentForPatient']);
    Route::get('/patients/{id}/history', [PatientController::class, 'history']);
    Route::get('/doctors/{id}/time-slots', [AppointmentController::class, 'getAvailableTimeSlots']);
    Route::get('/doctors', [AppointmentController::class, 'getAvailableDoctors']);
});

Route::middleware(['auth:sanctum', 'role:Doctor'])->prefix('doctor')->group(function () {
    // Appointment requests (pending approval)
    Route::get('/appointments/requests', [AppointmentRequestsController::class, 'index']);
    Route::put('/appointments/approve/{appointment_id}', [AppointmentRequestsController::class, 'approve']);
    Route::put('/appointments/reject/{appointment_id}', [AppointmentRequestsController::class, 'reject']);
    Route::put('/appointments/reschedule/{appointment_id}', [AppointmentRequestsController::class, 'reschedule']);

    // Today's appointments (approved)
    Route::get('/appointments/today', [DoctorAppointmentController::class, 'todayAppointments']);

    // Complete appointment with medical record
    Route::post('/appointments/{appointment_id}/complete', [DoctorAppointmentController::class, 'completeAppointment']);

    // All appointments (with optional filters)
    Route::get('/appointments', [DoctorAppointmentController::class, 'index']);

    // Medical Records (full CRUD for doctors)
    Route::get('/medical-records', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'index']);
    Route::post('/medical-records', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'store']);
    Route::get('/medical-records/{record_id}', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'show']);
    Route::put('/medical-records/{record_id}', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'update']);
    Route::delete('/medical-records/{record_id}', [\App\Http\Controllers\Doctor\MedicalRecordController::class, 'destroy']);
});
// Manager-only routes
Route::middleware(['auth:sanctum', 'role:Manager'])->prefix('manager')->group(function () {
    // Clinic settings management
    Route::get('/clinic/settings', [ManagerClinicController::class, 'getSettings']);
    Route::post('/clinic/settings', [ManagerClinicController::class, 'updateSettings']);
    Route::put('/clinic/settings', [ManagerClinicController::class, 'updateSettings']); // Keep PUT for backward compatibility

    // Get clinic logo
    Route::get('/clinic/logo', [ManagerClinicController::class, 'getLogo']);

    // Update own clinic logo (legacy route - kept for backward compatibility)
    Route::post('/clinic/logo', [ClinicRegistrationController::class, 'updateOwnClinicLogo']);

    // Staff management
    Route::post('/secretaries', [StaffController::class, 'addSecretary']);
    Route::post('/doctors', [StaffController::class, 'addDoctor']);
    Route::get('/staff', [StaffController::class, 'index']);
    Route::put('/staff/{user_id}', [StaffController::class, 'update_member']);
    Route::delete('/staff/{user_id}', [StaffController::class, 'delete_member']);
});

Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'stats']);


    // Clinic Management Routes
    Route::get('/clinics', [AdminClinicController::class, 'index']);
    Route::get('/clinics/{id}', [AdminClinicController::class, 'show']);
    Route::put('/clinics/{id}', [AdminClinicController::class, 'update']);
    Route::patch('/clinics/{id}/toggle-status', [AdminClinicController::class, 'toggleStatus']);
    Route::delete('/clinics/{id}', [AdminClinicController::class, 'destroy']);

    // Update clinic logo
    Route::post('/clinics/{clinic_id}/logo', [ClinicRegistrationController::class, 'updateLogo']);
});
