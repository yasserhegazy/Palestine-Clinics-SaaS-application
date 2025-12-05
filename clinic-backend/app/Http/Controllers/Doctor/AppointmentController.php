<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    /**
     * Get today's approved appointments for the doctor
     */
    public function todayAppointments(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can view their appointments',
            ], 403);
        }

        $doctor = Doctor::where('user_id', $user->user_id)->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        // Get today's approved appointments for the doctor
        $appointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->byStatus('Approved')
            ->today()
            ->with(['patient.user', 'clinic'])
            ->orderBy('appointment_time', 'asc')
            ->get();

        return response()->json([
            'appointments' => $appointments,
            'total' => $appointments->count(),
            'date' => now()->format('Y-m-d'),
        ], 200);
    }

    /**
     * Complete an appointment and create a medical record
     */
    public function completeAppointment(Request $request, $appointment_id)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can complete appointments',
            ], 403);
        }

        // Get the doctor record
        $doctor = Doctor::where('user_id', $user->user_id)->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        // Find the appointment
        $appointment = Appointment::findOrFail($appointment_id);

        // Check if appointment belongs to this doctor
        if ($appointment->doctor_id !== $doctor->doctor_id) {
            return response()->json([
                'message' => 'You do not have permission to complete this appointment',
            ], 403);
        }

        // Validate that the appointment is in "Approved" status
        if ($appointment->status !== 'Approved') {
            return response()->json([
                'message' => 'Only approved appointments can be completed',
                'current_status' => $appointment->status,
            ], 400);
        }

        // Use a database transaction to ensure data integrity
        DB::beginTransaction();
        try {
            // Prepare medical record data
            $medicalRecordRequest = new Request([
                'patient_id' => $appointment->patient_id,
                'visit_date' => now()->format('Y-m-d H:i:s'),
                'symptoms' => $request->input('symptoms'),
                'diagnosis' => $request->input('diagnosis'),
                'prescription' => $request->input('prescription', ''),
                'next_visit' => $request->input('next_visit'),
            ]);

            // Merge the user from the original request
            $medicalRecordRequest->setUserResolver(function () use ($user) {
                return $user;
            });

            // Call MedicalRecordController's store method
            $medicalRecordController = new MedicalRecordController();
            $medicalRecordResponse = $medicalRecordController->store($medicalRecordRequest);

            // Check if medical record was created successfully
            if ($medicalRecordResponse->status() !== 201) {
                DB::rollBack();
                return $medicalRecordResponse;
            }

            // Update appointment status to "Completed"
            $appointment->status = 'Completed';
            $appointment->save();

            DB::commit();

            // Load relationships for the response
            $appointment->load(['patient.user', 'clinic', 'doctor.user']);

            // Extract medical record from response
            $medicalRecordData = json_decode($medicalRecordResponse->getContent(), true);

            return response()->json([
                'message' => 'Appointment completed successfully',
                'appointment' => $appointment,
                'medical_record' => $medicalRecordData['record'] ?? null,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to complete appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all appointments for the doctor (optional: with filters)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'Doctor') {
            return response()->json([
                'message' => 'Only doctors can view their appointments',
            ], 403);
        }

        $doctor = Doctor::where('user_id', $user->user_id)->first();

        if (!$doctor) {
            return response()->json([
                'message' => 'Doctor profile not found',
            ], 404);
        }

        // Build query
        $query = Appointment::where('doctor_id', $doctor->doctor_id);

        // Apply filters if provided
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('appointment_date', $request->date);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        // Get appointments with relationships
        $appointments = $query->with(['patient.user', 'clinic'])
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'asc')
            ->get();

        return response()->json([
            'appointments' => $appointments,
            'total' => $appointments->count(),
        ], 200);
    }
}
