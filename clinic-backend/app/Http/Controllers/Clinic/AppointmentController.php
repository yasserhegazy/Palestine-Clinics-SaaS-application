<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    //
    public function getAvailableDoctors(Request $request)
    {
        $user = $request->user();

        $doctors = Doctor::with('user')
            ->whereHas('user', function ($query) use ($user) {
                $query->where('clinic_id', $user->clinic_id);
            })
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->doctor_id,
                    'name' => $doctor->user->name,
                    'specialization' => $doctor->specialization,
                    'clinic_room' => $doctor->clinic_room,
                ];
            });

        return response()->json([
            'data' => $doctors,
        ]);
    }

    public function createAppointmentForPatient(Request $request)
    {
        $user = $request->user();

        // Validate request
        $validated = $request->validate([
            'patientId' => 'required|integer|exists:patients,patient_id',
            'doctorId' => 'required|integer|exists:doctors,doctor_id',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|string',
            'notes' => 'nullable|string|max:500',
            // Payment fields (optional)
            'payment' => 'nullable|array',
            'payment.amount' => 'required_with:payment|numeric|min:0',
            'payment.amount_paid' => 'nullable|numeric|min:0',
            'payment.payment_method' => 'required_with:payment|in:Cash,Later,Partial,Exempt',
            'payment.notes' => 'nullable|string|max:500',
            'payment.exemption_reason' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Get doctor and verify they belong to the same clinic
            $doctor = Doctor::with('user')->findOrFail($validated['doctorId']);

            if ($doctor->user->clinic_id !== $user->clinic_id) {
                return response()->json([
                    'message' => 'Doctor does not belong to your clinic',
                ], 400);
            }

            // Check for conflicting appointments
            $existingAppointment = Appointment::where('doctor_id', $validated['doctorId'])
                ->where('appointment_date', $validated['date'])
                ->where('appointment_time', $validated['time'])
                ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
                ->first();

            if ($existingAppointment) {
                return response()->json([
                    'message' => 'This time slot is already booked',
                    'errors' => ['time' => ['This time slot is already booked. Please choose another time.']],
                ], 422);
            }

            // Determine payment status
            $paymentStatus = 'Pending';
            if (isset($validated['payment'])) {
                $paymentStatus = match ($validated['payment']['payment_method']) {
                    'Cash' => 'Paid',
                    'Exempt' => 'Exempt',
                    'Later' => 'Pending',
                    'Partial' => 'Partial',
                    default => 'Pending',
                };
            }

            // Create appointment
            $appointment = Appointment::create([
                'clinic_id' => $user->clinic_id,
                'doctor_id' => $validated['doctorId'],
                'patient_id' => $validated['patientId'],
                'secretary_id' => $user->user_id,
                'appointment_date' => $validated['date'],
                'appointment_time' => $validated['time'],
                'status' => 'Approved',
                'notes' => $validated['notes'] ?? null,
                'fee_amount' => $validated['payment']['amount'] ?? $doctor->consultation_fee ?? 0,
                'payment_status' => $paymentStatus,
            ]);

            $payment = null;

            // Create payment record if payment info provided
            if (isset($validated['payment'])) {
                $paymentData = $validated['payment'];
                $amountPaid = $paymentData['amount_paid'] ?? 0;

                // For cash payments, amount paid equals full amount
                if ($paymentData['payment_method'] === 'Cash') {
                    $amountPaid = $paymentData['amount'];
                }

                // For exempt, no payment needed
                if ($paymentData['payment_method'] === 'Exempt') {
                    $amountPaid = 0;
                }

                $payment = Payment::create([
                    'appointment_id' => $appointment->appointment_id,
                    'patient_id' => $validated['patientId'],
                    'clinic_id' => $user->clinic_id,
                    'received_by' => $user->user_id,
                    'amount' => $paymentData['amount'],
                    'amount_paid' => $amountPaid,
                    'payment_method' => $paymentData['payment_method'],
                    'status' => $paymentStatus,
                    'payment_date' => in_array($paymentStatus, ['Paid', 'Partial']) ? now() : null,
                    'notes' => $paymentData['notes'] ?? null,
                    'exemption_reason' => $paymentData['exemption_reason'] ?? null,
                ]);
            }

            DB::commit();

            // Load relationships
            $appointment->load(['doctor.user', 'patient.user', 'clinic', 'payment']);

            return response()->json([
                'message' => 'Appointment created successfully',
                'appointment' => $appointment,
                'payment' => $payment,
                'receipt_number' => $payment?->receipt_number,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating appointment: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to create appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPatientHistory($patient_id)
    {
        return response()->json([
            'appointments' => Appointment::where('patient_id', $patient_id)->get()
        ]);
    }

    public function getAvailableTimeSlots(Request $request, $id)
    {
        // Validate request
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        try {
            // Get doctor
            $doctor = Doctor::findOrFail($id);

            // Verify doctor belongs to the authenticated user's clinic
            if ($doctor->user->clinic_id !== auth()->user()->clinic_id) {
                return response()->json([
                    'error' => 'Unauthorized access to doctor'
                ], 403);
            }

            // Get available slots
            $availableSlots = $doctor->getAvailableSlots($validated['date']);

            return response()->json([
                'success' => true,
                'date' => $validated['date'],
                'doctor_id' => $doctor->doctor_id,
                'doctor_name' => $doctor->user->name,
                'slot_duration' => $doctor->slot_duration,
                'available_slots' => $availableSlots,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve available time slots',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
