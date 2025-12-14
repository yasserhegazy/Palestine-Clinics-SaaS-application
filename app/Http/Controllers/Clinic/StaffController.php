<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Appointment;
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
            'phone' => 'required|string|max:20',
        ]);

        $user = $request->user();

        // Ensure manager has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $phone = $this->normalizePhoneNumber($validated['phone']);

        try {
            // Default password for all new users
            $defaultPassword = '12345678';

            // Create secretary user
            $secretary = User::create([
                'clinic_id' => $user->clinic_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $phone,
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
            'phone' => 'required|string|max:20',
            'specialization' => 'required|string|max:100',
            'available_days' => 'required|string|max:100',
            'clinic_room' => 'required|string|max:50',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'slot_duration' => 'required|integer|min:5|max:120',
        ]);

        $user = $request->user();

        // Ensure manager has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $phone = $this->normalizePhoneNumber($validated['phone']);

        DB::beginTransaction();

        try {
            // Default password for all new users
            $defaultPassword = '12345678';

            // Create doctor user
            $doctorUser = User::create([
                'clinic_id' => $user->clinic_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $phone,
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
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'slot_duration' => $validated['slot_duration'],
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

    public function index(Request $request)
    {
        $authenticatedUser = $request->user();

        // Ensure manager has a clinic
        if (!$authenticatedUser->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        // Get all staff members in the clinic with doctor relationship
        $members = User::where('clinic_id', $authenticatedUser->clinic_id)
            ->whereNotIn('role', ['Manager', 'Patient'])
            ->with(['clinic', 'doctor'])
            ->get();

        // Transform the data to include doctor-specific fields
        $transformedMembers = $members->map(function ($member) {
            $data = [
                'user_id' => $member->user_id,
                'clinic_id' => $member->clinic_id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'role' => $member->role,
                'status' => $member->status,
                'created_at' => $member->created_at,
            ];

            // Add doctor-specific fields if the user is a doctor
            if ($member->role === 'Doctor' && $member->doctor) {
                $data['specialization'] = $member->doctor->specialization;
                $data['available_days'] = $member->doctor->available_days;
                $data['clinic_room'] = $member->doctor->clinic_room;
                $data['start_time'] = $member->doctor->start_time ? $member->doctor->start_time->format('H:i') : null;
                $data['end_time'] = $member->doctor->end_time ? $member->doctor->end_time->format('H:i') : null;
                $data['slot_duration'] = $member->doctor->slot_duration;
            }

            return $data;
        });

        return response()->json([
            'members' => $transformedMembers,
        ], 200);
    }

    public function update_member(Request $request, $user_id)
    {
        $authenticatedUser = $request->user();

        // Ensure manager has a clinic
        if (!$authenticatedUser->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        // Find the member to update
        $member = User::findOrFail($user_id);

        // Verify the member belongs to the same clinic
        if ($member->clinic_id !== $authenticatedUser->clinic_id) {
            return response()->json([
                'message' => 'You do not have permission to update this member',
            ], 403);
        }

        // Validate based on role
        $rules = [
            'name' => 'required|string|max:100',
            'email' => "required|email|max:100|unique:users,email,{$user_id},user_id",
            'phone' => 'required|string|max:20',
        ];

        // Add doctor-specific validation if updating a doctor
        if ($member->role === 'Doctor') {
            $rules['specialization'] = 'sometimes|string|max:100';
            $rules['available_days'] = 'sometimes|string|max:100';
            $rules['clinic_room'] = 'sometimes|string|max:50';
            $rules['start_time'] = 'sometimes|date_format:H:i';
            $rules['end_time'] = 'sometimes|date_format:H:i|after:start_time';
            $rules['slot_duration'] = 'sometimes|integer|min:5|max:120';
        }

        $validated = $request->validate($rules);

        DB::beginTransaction();

        try {
            // Update user information
            $member->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $this->normalizePhoneNumber($validated['phone']),
            ]);

            // Update doctor-specific information if applicable
            if ($member->role === 'Doctor' && $member->doctor) {
                $doctorData = [];
                
                if (isset($validated['specialization'])) {
                    $doctorData['specialization'] = $validated['specialization'];
                }
                if (isset($validated['available_days'])) {
                    $doctorData['available_days'] = $validated['available_days'];
                }
                if (isset($validated['clinic_room'])) {
                    $doctorData['clinic_room'] = $validated['clinic_room'];
                }
                if (isset($validated['start_time'])) {
                    $doctorData['start_time'] = $validated['start_time'];
                }
                if (isset($validated['end_time'])) {
                    $doctorData['end_time'] = $validated['end_time'];
                }
                if (isset($validated['slot_duration'])) {
                    $doctorData['slot_duration'] = $validated['slot_duration'];
                }

                if (!empty($doctorData)) {
                    $member->doctor->update($doctorData);
                }
            }

            DB::commit();

            // Reload relationships
            $member->load('doctor');

            return response()->json([
                'message' => 'Staff member updated successfully',
                'user' => $member,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update staff member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete_member(Request $request, $user_id)
    {
        $authenticatedUser = $request->user();

        // Ensure manager has a clinic
        if (!$authenticatedUser->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        // Find the member to delete
        $member = User::findOrFail($user_id);

        // Verify the member belongs to the same clinic
        if ($member->clinic_id !== $authenticatedUser->clinic_id) {
            return response()->json([
                'message' => 'You do not have permission to delete this member',
            ], 403);
        }

        // Prevent deleting managers or the authenticated user themselves
        if ($member->role === 'Manager') {
            return response()->json([
                'message' => 'Cannot delete a manager account',
            ], 403);
        }

        if ($member->user_id === $authenticatedUser->user_id) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        // Soft delete the member (data is preserved and can be restored)
        $member->delete();

        return response()->json([
            'message' => 'Staff member deleted successfully',
        ], 200);
    }

    /**
     * Normalize phone numbers to the +970 format used in the database
     */
    private function normalizePhoneNumber(string $phone): string
    {
        $phone = trim($phone);

        if ($phone === '') {
            return $phone;
        }

        if (str_starts_with($phone, '0')) {
            return '+970' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '+')) {
            return '+970' . $phone;
        }

        return $phone;
    }
}
