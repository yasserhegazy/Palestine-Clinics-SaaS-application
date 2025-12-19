<?php

namespace App\Http\Resources\Appointment;

use App\Http\Resources\Doctor\DoctorSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableSlotsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => $this->resource['date'],
            'doctor' => new DoctorSummaryResource($this->resource['doctor']),
            'slot_duration' => $this->resource['doctor']->slot_duration,
            'available_slots' => $this->resource['available_slots'],
        ];
    }
}
