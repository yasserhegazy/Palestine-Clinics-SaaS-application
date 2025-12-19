<?php

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->payment_id,
            'appointment_id' => $this->appointment_id,
            'patient_id' => $this->patient_id,
            'clinic_id' => $this->clinic_id,
            'received_by' => $this->received_by,
            'amount' => $this->amount,
            'amount_paid' => $this->amount_paid,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'payment_date' => optional($this->payment_date)->toISOString(),
            'receipt_number' => $this->receipt_number,
            'notes' => $this->notes,
            'exemption_reason' => $this->exemption_reason,
        ];
    }
}
