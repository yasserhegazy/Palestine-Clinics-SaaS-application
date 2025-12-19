<?php

namespace App\Http\Resources\Patient;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date' => data_get($this, 'date'),
            'clinic' => data_get($this, 'clinic'),
            'diagnosis' => data_get($this, 'diagnosis'),
            'doctor' => data_get($this, 'doctor'),
        ];
    }
}
