<?php

namespace App\Http\Resources\Manager\Clinic;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicSettingsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'clinic_id' => $this->clinic_id,
            'name' => $this->name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo_path' => $this->logo_path,
            'logo_url' => $this->logo_path ? url('storage/' . $this->logo_path) : null,
            'subscription_plan' => $this->subscription_plan,
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
