<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;

class AppointmentRequestsController extends Controller
{
    //
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can view their appointment requests',
            ], 403);
        }

        $doctor = Doctor::where('user_id', $user->user_id)->first();
        
        // Check if doctor record exists
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        // Get appointment requests for the doctor
        $appointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->byStatus('Requested')  // Use the scope method
            ->with(['patient.user', 'clinic'])
            ->orderBy('appointment_date', 'asc')  // Oldest first
            ->get();

        return response()->json([
            'appointments' => $appointments,
        ], 200);
    }
    public function approve(Request $request, $appointment_id)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can approve appointment requests',
            ], 403);
        }

        // Get the doctor record
        $doctor = Doctor::where('user_id', $user->user_id)->first();
        
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        // Use the route parameter, not $request->appointment_id
        $appointment = Appointment::findOrFail($appointment_id);

        // Check if appointment belongs to this doctor
        if ($appointment->doctor_id !== $doctor->doctor_id) {
            return response()->json([
                'message' => 'You do not have permission to approve this appointment request',
            ], 403);
        }

        // Validate that the appointment is in "Requested" status
        if ($appointment->status !== 'Requested') {
            return response()->json([
                'message' => 'Only appointments with "Requested" status can be approved',
                'current_status' => $appointment->status,
            ], 400);
        }

        // Approve the appointment
        $appointment->status = 'Approved';
        $appointment->save();

        // Load relationships for the response
        $appointment->load(['patient.user', 'clinic']);

        return response()->json([
            'message' => 'Appointment request approved successfully',
            'appointment' => $appointment,
        ], 200);
    }
    
    public function reject(Request $request, $appointment_id)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can reject appointment requests',
            ], 403);
        }

        // Get the doctor record
        $doctor = Doctor::where('user_id', $user->user_id)->first();
        
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        // Validate rejection reason
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        // Use the route parameter, not $request->appointment_id
        $appointment = Appointment::findOrFail($appointment_id);

        // Check if appointment belongs to this doctor
        if ($appointment->doctor_id !== $doctor->doctor_id) {
            return response()->json([
                'message' => 'You do not have permission to reject this appointment request',
            ], 403);
        }

        // Validate that the appointment is in "Requested" status
        if ($appointment->status !== 'Requested') {
            return response()->json([
                'message' => 'Only appointments with "Requested" status can be rejected',
                'current_status' => $appointment->status,
            ], 400);
        }

        // Reject the appointment (use 'Cancelled' status, not 'Rejected')
        $appointment->status = 'Cancelled';
        $appointment->rejection_reason = $validated['rejection_reason'];
        $appointment->save();

        // Load relationships for the response
        $appointment->load(['patient.user', 'clinic']);

        return response()->json([
            'message' => 'Appointment request rejected successfully',
            'appointment' => $appointment,
        ], 200);
    }

    public function reschedule(Request $request, $appointment_id)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can reschedule appointment requests',
            ], 403);
        } 
        
        $doctor = $user->doctor;
        
        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        $appointment = Appointment::findOrFail($appointment_id);

        if ($appointment->doctor_id !== $doctor->doctor_id) {
            return response()->json([
                'message' => 'You do not have permission to reschedule this appointment request',
            ], 403);
        }

        $validated = $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
        ]);

        $appointment->appointment_date = $validated['appointment_date'];
        $appointment->appointment_time = $validated['appointment_time'];
        $appointment->status = 'Approved'; 
        $appointment->save();

        return response()->json([
            'message' => 'Appointment rescheduled successfully',
            'appointment' => $appointment,
        ], 200);
    }
}
