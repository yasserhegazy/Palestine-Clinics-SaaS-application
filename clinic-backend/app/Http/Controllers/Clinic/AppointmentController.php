<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;

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
        ]);

        try {
            // Get doctor and verify they belong to the same clinic
            $doctor = Doctor::with('user')->findOrFail($validated['doctorId']);

            if ($doctor->user->clinic_id !== $user->clinic_id) {
                return response()->json([
                    'message' => 'Doctor does not belong to your clinic',
                ], 400);
            }

            // Combine date and time into datetime
            $appointmentDateTime = $validated['date'] . ' ' . $validated['time'];

            // Check for conflicting appointments
            $existingAppointment = Appointment::where('doctor_id', $validated['doctorId'])
                ->where('appointment_date', $appointmentDateTime)
                ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
                ->first();

            if ($existingAppointment) {
                return response()->json([
                    'message' => 'This time slot is already booked',
                    'errors' => ['time' => ['This time slot is already booked. Please choose another time.']],
                ], 422);
            }

            // Create appointment
            $appointment = Appointment::create([
                'clinic_id' => $user->clinic_id,
                'doctor_id' => $validated['doctorId'],
                'patient_id' => $validated['patientId'],
                'secretary_id' => $user->user_id,
                'appointment_date' => $appointmentDateTime,
                'status' => 'Approved',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Load relationships
            $appointment->load(['doctor.user', 'patient.user', 'clinic']);

            return response()->json([
                'message' => 'Appointment created successfully',
                'appointment' => $appointment,
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating appointment: ' . $e->getMessage());

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
