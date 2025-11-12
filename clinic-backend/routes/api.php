<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;

// Handle preflight OPTIONS requests for all routes
Route::options('{any}', function (Request $request) {
    return response('', 200);
})->where('any', '.*');

// Unified authentication routes (single login endpoint for all users).
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});
