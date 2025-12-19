<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->doctor_id,
            'name' => data_get($this, 'user.name'),
            'specialization' => $this->specialization,
            'clinic_room' => $this->clinic_room,
        ];
    }
}
