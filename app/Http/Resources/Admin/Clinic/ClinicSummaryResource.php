<?php

namespace App\Http\Resources\Admin\Clinic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->clinic_id,
            'name' => $this->name,
            'speciality' => $this->speciality,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'subscription_plan' => $this->subscription_plan,
            'status' => $this->status,
            'logo_url' => $this->logo_url ?? null,
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
