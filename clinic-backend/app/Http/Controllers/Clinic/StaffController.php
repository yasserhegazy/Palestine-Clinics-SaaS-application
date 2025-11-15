<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    /**
     * Add a new secretary to the clinic
     */
    public function addSecretary(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'required|string|max:20|regex:/^\+970[0-9]{9}$/',
        ]);

        $user = $request->user();

        // Ensure manager has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        try {
            // Default password for all new users
            $defaultPassword = '12345678';

            // Create secretary user
            $secretary = User::create([
                'clinic_id' => $user->clinic_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password_hash' => Hash::make($defaultPassword),
                'role' => 'Secretary',
                'status' => 'Active',
            ]);

            return response()->json([
                'message' => 'Secretary added successfully',
                'secretary' => $secretary,
                'temporary_password' => $defaultPassword,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add secretary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new doctor to the clinic
     */
    public function addDoctor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'required|string|max:20|regex:/^\+970[0-9]{9}$/',
            'specialization' => 'required|string|max:100',
            'available_days' => 'required|string|max:100',
            'clinic_room' => 'required|string|max:50',
        ]);

        $user = $request->user();

        // Ensure manager has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Default password for all new users
            $defaultPassword = '12345678';

            // Create doctor user
            $doctorUser = User::create([
                'clinic_id' => $user->clinic_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password_hash' => Hash::make($defaultPassword),
                'role' => 'Doctor',
                'status' => 'Active',
            ]);

            // Create doctor profile
            $doctor = Doctor::create([
                'user_id' => $doctorUser->user_id,
                'specialization' => $validated['specialization'],
                'available_days' => $validated['available_days'],
                'clinic_room' => $validated['clinic_room'],
            ]);

            DB::commit();

            // Load user relationship for full data
            $doctor->load('user');

            return response()->json([
                'message' => 'Doctor added successfully',
                'doctor' => $doctor,
                'user' => $doctorUser,
                'temporary_password' => $defaultPassword,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to add doctor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
