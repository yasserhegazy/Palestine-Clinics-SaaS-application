<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClinicRegistrationController extends Controller
{
    /**
     * Register a new clinic with manager account
     */
    public function register(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            // Clinic validation
            'clinic.name' => 'required|string|max:100',
            'clinic.speciality' => 'nullable|string|max:100',
            'clinic.address' => 'required|string|max:255',
            'clinic.phone' => 'required|string|max:20|regex:/^\+970[0-9]{3}[0-9]{6}$/',
            'clinic.email' => 'required|email|max:100|unique:clinics,email',
            'clinic.subscription_plan' => 'required|in:Basic,Standard,Premium',

            // Manager validation
            'manager.name' => 'required|string|max:100',
            'manager.email' => 'required|email|max:100|unique:users,email',
            'manager.phone' => 'required|string|max:20|regex:/^\+970[0-9]{3}[0-9]{6}$/',
            'manager.password' => 'required|string|min:8|confirmed',
        ]);

        // Validate logo separately (not nested)
        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
        }

        DB::beginTransaction();

        try {
            $clinicData = [
                'name' => $validated['clinic']['name'],
                'speciality' => $validated['clinic']['speciality'] ?? null,
                'address' => $validated['clinic']['address'],
                'phone' => $validated['clinic']['phone'],
                'email' => $validated['clinic']['email'],
                'subscription_plan' => $validated['clinic']['subscription_plan'],
                'status' => 'Active',
            ];

            // Handle logo upload if provided
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $logoPath = $logo->store('clinics/logos', 'public');
                $clinicData['logo'] = $logoPath;
            }

            // Create clinic
            $clinic = Clinic::create($clinicData);

            // Create manager user for this clinic
            $manager = User::create([
                'clinic_id' => $clinic->clinic_id,
                'name' => $validated['manager']['name'],
                'email' => $validated['manager']['email'],
                'phone' => $validated['manager']['phone'],
                'password_hash' => Hash::make($validated['manager']['password']),
                'role' => 'Manager',
                'status' => 'Active',
            ]);

            DB::commit();

            // Refresh clinic to get appended attributes
            $clinic->refresh();

            // Return success with login token
            $token = $manager->createToken('manager-token')->plainTextToken;

            return response()->json([
                'message' => 'Clinic registered successfully',
                'clinic' => $clinic,
                'manager' => $manager,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded logo if transaction fails
            if (isset($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update clinic logo (for existing clinics)
     */
    public function updateLogo(Request $request, $clinic_id)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $clinic = Clinic::findOrFail($clinic_id);

        // Delete old logo if exists
        if ($clinic->logo) {
            Storage::disk('public')->delete($clinic->logo);
        }

        // Upload new logo
        $logo = $request->file('logo');
        $logoPath = $logo->store('clinics/logos', 'public');

        $clinic->logo = $logoPath;
        $clinic->save();

        return response()->json([
            'message' => 'Logo updated successfully',
            'logo_url' => Storage::url($logoPath),
        ]);
    }

    /**
     * Update own clinic logo (for managers)
     */
    public function updateOwnClinicLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = $request->user();

        // Ensure manager has a clinic
        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $clinic = Clinic::findOrFail($user->clinic_id);

        // Delete old logo if exists
        if ($clinic->logo) {
            Storage::disk('public')->delete($clinic->logo);
        }

        // Upload new logo
        $logo = $request->file('logo');
        $logoPath = $logo->store('clinics/logos', 'public');

        $clinic->logo = $logoPath;
        $clinic->save();

        return response()->json([
            'message' => 'Logo updated successfully',
            'logo_url' => Storage::url($logoPath),
        ]);
    }
}
