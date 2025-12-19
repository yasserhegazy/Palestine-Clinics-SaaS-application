<?php

namespace App\Http\Resources\Appointment;

use App\Http\Resources\Doctor\DoctorSummaryResource;
use App\Http\Resources\Patient\PatientSummaryResource;
use App\Http\Resources\Payment\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->appointment_id,
            'clinic_id' => $this->clinic_id,
            'doctor_id' => $this->doctor_id,
            'patient_id' => $this->patient_id,
            'secretary_id' => $this->secretary_id,
            'date' => optional($this->appointment_date)->toDateString(),
            'time' => $this->appointment_time,
            'status' => $this->status,
            'notes' => $this->notes,
            'fee_amount' => $this->fee_amount,
            'payment_status' => $this->payment_status,
            'doctor' => DoctorSummaryResource::make($this->whenLoaded('doctor')),
            'patient' => PatientSummaryResource::make($this->whenLoaded('patient')),
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
            'clinic' => $this->whenLoaded('clinic', fn () => [
                'id' => $this->clinic->clinic_id,
                'name' => $this->clinic->name ?? null,
            ]),
        ];
    }
}
