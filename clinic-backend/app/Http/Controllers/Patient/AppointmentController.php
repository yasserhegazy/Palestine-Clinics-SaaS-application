<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    /**
     * Create a new appointment request by patient
     */
    public function createAppointment(Request $request)
    {
        $user = $request->user();

        // Verify user is a patient
        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can create appointments',
            ], 403);
        }

        // Get patient record
        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        // Validate request
        $validated = $request->validate([
            'doctor_id' => 'required|integer|exists:doctors,doctor_id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        // Get doctor and verify they belong to the same clinic
        $doctor = Doctor::with('user')->findOrFail($validated['doctor_id']);
        
        if ($doctor->user->clinic_id !== $user->clinic_id) {
            return response()->json([
                'message' => 'Doctor does not belong to your clinic',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Check for conflicting appointments for the same doctor at the same date and time
            $existingAppointment = Appointment::where('doctor_id', $validated['doctor_id'])
                ->where('appointment_date', $validated['appointment_date'])
                ->where('appointment_time', $validated['appointment_time'])
                ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
                ->first();

            if ($existingAppointment) {
                throw ValidationException::withMessages([
                    'appointment_time' => ['This time slot is already booked. Please choose another time.'],
                ]);
            }

            // Create appointment with status 'Requested'
            $appointment = Appointment::create([
                'clinic_id' => $user->clinic_id,
                'doctor_id' => $validated['doctor_id'],
                'patient_id' => $patient->patient_id,
                'secretary_id' => null, // Patient creates their own appointment
                'appointment_date' => $validated['appointment_date'],
                'appointment_time' => $validated['appointment_time'],
                'status' => 'Requested',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            // Load relationships
            $appointment->load(['doctor.user', 'patient.user', 'clinic']);

            return response()->json([
                'message' => 'Appointment request created successfully. You will be notified when it is approved.',
                'appointment' => $appointment,
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all appointments for the authenticated patient
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can view their appointments',
            ], 403);
        }

        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        // Get appointments with relationships
        $appointments = Appointment::where('patient_id', $patient->patient_id)
            ->with(['doctor.user', 'clinic'])
            ->orderBy('appointment_date', 'desc')
            ->get();

        return response()->json([
            'appointments' => $appointments,
        ], 200);
    }

    /**
     * Get upcoming appointments for the authenticated patient
     */
    public function upcoming(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can view their appointments',
            ], 403);
        }

        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        // Get upcoming appointments
        $appointments = Appointment::where('patient_id', $patient->patient_id)
            ->where('appointment_date', '>', now())
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->with(['doctor.user', 'clinic'])
            ->orderBy('appointment_date', 'asc')
            ->get();

        return response()->json([
            'appointments' => $appointments,
        ], 200);
    }

    /**
     * Get a specific appointment
     */
    public function show(Request $request, $appointment_id)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can view their appointments',
            ], 403);
        }

        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        $appointment = Appointment::where('appointment_id', $appointment_id)
            ->where('patient_id', $patient->patient_id)
            ->with(['doctor.user', 'clinic', 'secretary'])
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found',
            ], 404);
        }

        return response()->json([
            'appointment' => $appointment,
        ], 200);
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, $appointment_id)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can cancel their appointments',
            ], 403);
        }

        $patient = Patient::where('user_id', $user->user_id)->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient record not found',
            ], 404);
        }

        $appointment = Appointment::where('appointment_id', $appointment_id)
            ->where('patient_id', $patient->patient_id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'message' => 'Appointment not found',
            ], 404);
        }

        // Can only cancel appointments that are not completed or already cancelled
        if (in_array($appointment->status, ['Completed', 'Cancelled'])) {
            return response()->json([
                'message' => 'Cannot cancel this appointment',
            ], 400);
        }

        $appointment->update([
            'status' => 'Cancelled',
        ]);

        return response()->json([
            'message' => 'Appointment cancelled successfully',
            'appointment' => $appointment,
        ], 200);
    }

    /**
     * Get available doctors for the patient's clinic
     */
    public function getAvailableDoctors(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Patient') {
            return response()->json([
                'message' => 'Only patients can view available doctors',
            ], 403);
        }

        // Get all active doctors in the patient's clinic
        $doctors = User::where('clinic_id', $user->clinic_id)
            ->where('role', 'Doctor')
            ->where('status', 'Active')
            ->with('doctor')
            ->get()
            ->map(function ($user) {
                return [
                    'doctor_id' => $user->doctor->doctor_id,
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'specialization' => $user->doctor->specialization,
                    'available_days' => $user->doctor->available_days,
                    'clinic_room' => $user->doctor->clinic_room,
                    'start_time' => $user->doctor->start_time ? $user->doctor->start_time->format('H:i') : null,
                    'end_time' => $user->doctor->end_time ? $user->doctor->end_time->format('H:i') : null,
                    'slot_duration' => $user->doctor->slot_duration,
                ];
            });

        return response()->json([
            'doctors' => $doctors,
        ], 200);
    }
}
