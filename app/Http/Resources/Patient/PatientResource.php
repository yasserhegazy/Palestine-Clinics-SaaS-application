<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->patient_id,
            'national_id' => $this->national_id,
            'date_of_birth' => optional($this->date_of_birth)->toDateString(),
            'gender' => $this->gender,
            'address' => $this->address,
            'blood_type' => $this->blood_type,
            'allergies' => $this->allergies,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->user_id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'clinic_id' => $this->user->clinic_id,
            ]),
        ];
    }
}
