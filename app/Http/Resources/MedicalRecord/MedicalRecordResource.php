<?php

namespace App\Http\Resources\MedicalRecord;

use App\Http\Resources\Doctor\DoctorSummaryResource;
use App\Http\Resources\Patient\PatientSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->record_id,
            'visit_date' => optional($this->visit_date)->format('Y-m-d'),
            'symptoms' => $this->symptoms ?? '',
            'diagnosis' => $this->diagnosis ?? '',
            'prescription' => $this->prescription ?? '',
            'doctor' => DoctorSummaryResource::make($this->whenLoaded('doctor')),
            'patient' => PatientSummaryResource::make($this->whenLoaded('patient')),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
