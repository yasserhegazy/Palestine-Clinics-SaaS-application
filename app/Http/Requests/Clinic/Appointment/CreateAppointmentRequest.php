<?php

namespace App\Http\Requests\Clinic\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class CreateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->clinic_id !== null;
    }

    public function rules(): array
    {
        return [
            'patientId' => ['required', 'integer', 'exists:patients,patient_id'],
            'doctorId' => ['required', 'integer', 'exists:doctors,doctor_id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment' => ['nullable', 'array'],
            'payment.amount' => ['required_with:payment', 'numeric', 'min:0'],
            'payment.amount_paid' => ['nullable', 'numeric', 'min:0'],
            'payment.payment_method' => ['required_with:payment', 'in:Cash,Later,Partial,Exempt'],
            'payment.notes' => ['nullable', 'string', 'max:500'],
            'payment.exemption_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function appointmentData(): array
    {
        $validated = $this->validated();

        return [
            'patient_id' => $validated['patientId'],
            'doctor_id' => $validated['doctorId'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'notes' => $validated['notes'] ?? null,
            'payment' => isset($validated['payment'])
                ? array_merge($validated['payment'], ['patient_id' => $validated['patientId']])
                : null,
        ];
    }
}
