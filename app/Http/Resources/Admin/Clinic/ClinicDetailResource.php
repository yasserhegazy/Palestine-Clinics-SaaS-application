<?php

namespace App\Http\Resources\Admin\Clinic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usersGrouped = $this->relationLoaded('users')
            ? $this->users->groupBy('role')
            : collect();

        $appointments = $this->relationLoaded('appointments')
            ? $this->appointments
            : collect();

        $appointmentStats = $appointments->isNotEmpty() ? [
            'total' => $appointments->count(),
            'requested' => $appointments->where('status', 'Requested')->count(),
            'pending' => $appointments->where('status', 'Pending Doctor Approval')->count(),
            'approved' => $appointments->where('status', 'Approved')->count(),
            'completed' => $appointments->where('status', 'Completed')->count(),
            'cancelled' => $appointments->where('status', 'Cancelled')->count(),
            'rejected' => 0,
        ] : [];

        return [
            'id' => $this->clinic_id,
            'name' => $this->name,
            'speciality' => $this->speciality,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'subscription_plan' => $this->subscription_plan,
            'subscription_start' => $this->subscription_start,
            'subscription_end' => $this->subscription_end,
            'status' => $this->status,
            'logo_url' => $this->logo_url ?? null,
            'users' => [
                'doctors' => $usersGrouped->get('Doctor', collect())->map($this->mapUser()),
                'patients' => $usersGrouped->get('Patient', collect())->map($this->mapUser()),
                'secretaries' => $usersGrouped->get('Secretary', collect())->map($this->mapUser()),
                'managers' => $usersGrouped->get('Manager', collect())->map($this->mapUser()),
            ],
            'user_counts' => [
                'total' => $this->users()->count(),
                'doctors' => $usersGrouped->get('Doctor', collect())->count(),
                'patients' => $usersGrouped->get('Patient', collect())->count(),
                'secretaries' => $usersGrouped->get('Secretary', collect())->count(),
                'managers' => $usersGrouped->get('Manager', collect())->count(),
                'active' => $this->users()->where('status', 'Active')->count(),
                'inactive' => $this->users()->where('status', 'Inactive')->count(),
            ],
            'appointment_stats' => $appointmentStats,
            'subscription' => $this->subscriptionDetails(),
        ];
    }

    private function mapUser(): \Closure
    {
        return static fn ($user) => [
            'user_id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'created_at' => optional($user->created_at)->toISOString(),
        ];
    }

    private function subscriptionDetails(): ?array
    {
        if (!$this->subscription_start || !$this->subscription_end) {
            return null;
        }

        $now = now();
        $endDate = \Carbon\Carbon::parse($this->subscription_end);
        $daysRemaining = $now->diffInDays($endDate, false);

        return [
            'plan' => $this->subscription_plan,
            'start_date' => $this->subscription_start,
            'end_date' => $this->subscription_end,
            'days_remaining' => max(0, (int) $daysRemaining),
            'is_active' => $daysRemaining > 0,
            'is_expiring_soon' => $daysRemaining > 0 && $daysRemaining <= 30,
        ];
    }
}
