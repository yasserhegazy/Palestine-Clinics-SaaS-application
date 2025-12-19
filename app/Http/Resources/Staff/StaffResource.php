<?php

namespace App\Http\Resources\Staff;

use App\Http\Resources\Doctor\DoctorSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'clinic_id' => $this->clinic_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toISOString(),
            'doctor' => $this->when($this->role === 'Doctor', fn () => [
                'specialization' => $this->doctor?->specialization,
                'available_days' => $this->doctor?->available_days,
                'clinic_room' => $this->doctor?->clinic_room,
                'start_time' => $this->doctor?->start_time?->format('H:i'),
                'end_time' => $this->doctor?->end_time?->format('H:i'),
                'slot_duration' => $this->doctor?->slot_duration,
            ]),
        ];
    }
}
