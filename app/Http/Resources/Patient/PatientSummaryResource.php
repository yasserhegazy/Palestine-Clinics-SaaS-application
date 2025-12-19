<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->patient_id,
            'name' => data_get($this, 'user.name'),
            'email' => data_get($this, 'user.email'),
            'phone' => data_get($this, 'user.phone'),
            'national_id' => $this->national_id,
            'date_of_birth' => optional($this->date_of_birth)->toDateString(),
            'address' => $this->address,
        ];
    }
}
