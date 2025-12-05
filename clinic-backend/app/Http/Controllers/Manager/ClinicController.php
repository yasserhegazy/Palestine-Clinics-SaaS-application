<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClinicController extends Controller
{
    /**
     * Update clinic settings (Manager only)
     */
    public function updateSettings(Request $request)
    {
        $user = $request->user();

        // Verify user is a manager
        if ($user->role !== 'Manager') {
            return response()->json([
                'success' => false,
                'message' => 'Only managers can update clinic settings',
            ], 403);
        }

        // Get the manager's clinic
        $clinic = Clinic::where('clinic_id', $user->clinic_id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        // Log incoming request for debugging
        \Log::info('Clinic update request', [
            'has_file' => $request->hasFile('logo'),
            'file_size' => $request->hasFile('logo') ? $request->file('logo')->getSize() : null,
            'file_mime' => $request->hasFile('logo') ? $request->file('logo')->getMimeType() : null,
            'all_data' => $request->except('logo'),
        ]);

        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'address' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:100|unique:clinics,email,' . $clinic->clinic_id . ',clinic_id',
            'subscription_plan' => 'sometimes|in:Basic,Standard,Premium',
            'status' => 'sometimes|in:Active,Inactive',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,jpg,png,gif,svg|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', ['errors' => $validator->errors()->toArray()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepare fields that were actually provided
        $updateData = [];

        if ($request->has('name')) $updateData['name'] = $request->input('name');
        if ($request->has('address')) $updateData['address'] = $request->input('address');
        if ($request->has('phone')) $updateData['phone'] = $request->input('phone');
        if ($request->has('email')) $updateData['email'] = $request->input('email');
        if ($request->has('subscription_plan')) $updateData['subscription_plan'] = $request->input('subscription_plan');
        if ($request->has('status')) $updateData['status'] = $request->input('status');

        // Handle logo upload if provided
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($clinic->logo_path && Storage::disk('public')->exists($clinic->logo_path)) {
                Storage::disk('public')->delete($clinic->logo_path);
            }

            // Store new logo in public disk
            $logoPath = $request->file('logo')->store('clinic-logos', 'public');
            $updateData['logo_path'] = $logoPath;
        }

        // Update the clinic
        if (!empty($updateData)) {
            $clinic->update($updateData);
        }

        // Refresh clinic to get updated data
        $clinic->refresh();

        // Generate full logo URL
        $logoUrl = null;
        if ($clinic->logo_path) {
            // logo_path already contains 'clinic-logos/filename.jpg'
            $logoUrl = url('storage/' . $clinic->logo_path);
        }

        return response()->json([
            'success' => true,
            'message' => 'Clinic settings updated successfully',
            'clinic' => [
                'clinic_id' => $clinic->clinic_id,
                'name' => $clinic->name,
                'address' => $clinic->address,
                'phone' => $clinic->phone,
                'email' => $clinic->email,
                'logo_path' => $clinic->logo_path,
                'logo_url' => $logoUrl,
                'subscription_plan' => $clinic->subscription_plan,
                'status' => $clinic->status,
                'created_at' => $clinic->created_at,
                'updated_at' => $clinic->updated_at,
            ],
        ], 200);
    }

    /**
     * Get current clinic settings (Manager only)
     */
    public function getSettings(Request $request)
    {
        $user = $request->user();

        // Verify user is a manager
        if ($user->role !== 'Manager') {
            return response()->json([
                'success' => false,
                'message' => 'Only managers can view clinic settings',
            ], 403);
        }

        // Get the manager's clinic
        $clinic = Clinic::where('clinic_id', $user->clinic_id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        // Generate full logo URL
        $logoUrl = null;
        if ($clinic->logo_path) {
            // logo_path already contains 'clinic-logos/filename.jpg'
            $logoUrl = url('storage/' . $clinic->logo_path);
        }

        return response()->json([
            'success' => true,
            'clinic' => [
                'clinic_id' => $clinic->clinic_id,
                'name' => $clinic->name,
                'address' => $clinic->address,
                'phone' => $clinic->phone,
                'email' => $clinic->email,
                'logo_path' => $clinic->logo_path,
                'logo_url' => $logoUrl,
                'subscription_plan' => $clinic->subscription_plan,
                'status' => $clinic->status,
                'created_at' => $clinic->created_at,
                'updated_at' => $clinic->updated_at,
            ],
        ], 200);
    }

    /**
     * Get clinic logo (Manager only)
     */
    public function getLogo(Request $request)
    {
        $user = $request->user();

        // Verify user is a manager
        if ($user->role !== 'Manager') {
            return response()->json([
                'success' => false,
                'message' => 'Only managers can view clinic logo',
            ], 403);
        }

        // Get the manager's clinic
        $clinic = Clinic::where('clinic_id', $user->clinic_id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        // Generate full logo URL
        $logoUrl = null;
        if ($clinic->logo_path) {
            // logo_path already contains 'clinic-logos/filename.jpg'
            $logoUrl = url('storage/' . $clinic->logo_path);
        }

        return response()->json([
            'success' => true,
            'logo_path' => $clinic->logo_path,
            'logo_url' => $logoUrl,
        ], 200);
    }
}
