<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClinicController extends Controller
{
    /**
     * Get all clinics with optional filters
     */
    public function index(Request $request)
    {
        $query = Clinic::select('clinics.*')->withCount('users');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search by name or location
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $clinics = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'clinics' => $clinics,
        ]);
    }

    /**
     * Get a single clinic by ID with detailed analytics
     */
    public function show($id)
    {
        $clinic = Clinic::where('clinic_id', $id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        // Get users grouped by role
        $users = $clinic->users()->get()->groupBy('role');

        $usersData = [
            'doctors' => $users->get('Doctor', collect())->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            })->values(),
            'patients' => $users->get('Patient', collect())->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            })->values(),
            'secretaries' => $users->get('Secretary', collect())->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            })->values(),
            'managers' => $users->get('Manager', collect())->map(function ($user) {
                return [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            })->values(),
        ];

        // Get appointment statistics
        $allAppointments = $clinic->appointments()->get();
        $appointmentStats = [
            'total' => $allAppointments->count(),
            'requested' => $allAppointments->where('status', 'Requested')->count(),
            'pending' => $allAppointments->where('status', 'Pending Doctor Approval')->count(),
            'approved' => $allAppointments->where('status', 'Approved')->count(),
            'completed' => $allAppointments->where('status', 'Completed')->count(),
            'cancelled' => $allAppointments->where('status', 'Cancelled')->count(),
            'rejected' => 0, // This status doesn't exist in the current schema
        ];

        // Calculate subscription details
        $subscriptionDetails = null;
        if ($clinic->subscription_start && $clinic->subscription_end) {
            $now = now();
            $endDate = \Carbon\Carbon::parse($clinic->subscription_end);
            $daysRemaining = $now->diffInDays($endDate, false);

            $subscriptionDetails = [
                'plan' => $clinic->subscription_plan,
                'start_date' => $clinic->subscription_start,
                'end_date' => $clinic->subscription_end,
                'days_remaining' => max(0, (int)$daysRemaining),
                'is_active' => $daysRemaining > 0,
                'is_expiring_soon' => $daysRemaining > 0 && $daysRemaining <= 30,
            ];
        }

        // User counts
        $userCounts = [
            'total' => $clinic->users()->count(),
            'doctors' => $usersData['doctors']->count(),
            'patients' => $usersData['patients']->count(),
            'secretaries' => $usersData['secretaries']->count(),
            'managers' => $usersData['managers']->count(),
            'active' => $clinic->users()->where('status', 'Active')->count(),
            'inactive' => $clinic->users()->where('status', 'Inactive')->count(),
        ];

        return response()->json([
            'success' => true,
            'clinic' => $clinic,
            'users' => $usersData,
            'user_counts' => $userCounts,
            'appointment_stats' => $appointmentStats,
            'subscription' => $subscriptionDetails,
        ]);
    }

    /**
     * Update clinic details
     */
    public function update(Request $request, $id)
    {
        $clinic = Clinic::where('clinic_id', $id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'email' => 'sometimes|email|max:255|unique:clinics,email,' . $id,
            'subscription_plan' => 'sometimes|in:Basic,Standard,Premium',
            'subscription_start' => 'sometimes|date',
            'subscription_end' => 'sometimes|date|after:subscription_start',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $clinic->update($request->only([
            'name',
            'location',
            'phone',
            'email',
            'subscription_plan',
            'subscription_start',
            'subscription_end',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Clinic updated successfully',
            'clinic' => $clinic->fresh(),
        ]);
    }

    /**
     * Toggle clinic status (Active/Inactive)
     */
    public function toggleStatus($id)
    {
        $clinic = Clinic::where('clinic_id', $id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        // Toggle status
        $newStatus = $clinic->status === 'Active' ? 'Inactive' : 'Active';
        $clinic->status = $newStatus;
        $clinic->save();

        return response()->json([
            'success' => true,
            'message' => "Clinic {$newStatus} successfully",
            'clinic' => $clinic,
        ]);
    }

    /**
     * Delete a clinic (soft delete recommended in production)
     */
    public function destroy($id)
    {
        $clinic = Clinic::where('clinic_id', $id)->first();

        if (!$clinic) {
            return response()->json([
                'success' => false,
                'message' => 'Clinic not found',
            ], 404);
        }

        // Check if clinic has users
        if ($clinic->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete clinic with existing users. Please deactivate instead.',
            ], 400);
        }

        $clinic->delete();

        return response()->json([
            'success' => true,
            'message' => 'Clinic deleted successfully',
        ]);
    }
}
