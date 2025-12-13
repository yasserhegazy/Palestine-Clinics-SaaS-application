<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\Carbon;
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
            ->get()
            ->map(function ($appointment) {
                return [
                    'appointment_id' => $appointment->appointment_id,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                    'notes' => $appointment->notes,
                    'patient' => [
                        'patient_id' => $appointment->patient->patient_id,
                        'name' => $appointment->patient->user->name,
                        'phone' => $appointment->patient->user->phone,
                        'national_id' => $appointment->patient->national_id,
                        'date_of_birth' => $appointment->patient->date_of_birth,
                        'gender' => $appointment->patient->gender,
                        'blood_type' => $appointment->patient->blood_type,
                        'allergies' => $appointment->patient->allergies,
                    ],
                ];
            });

        return response()->json([
            'appointments' => $appointments,
            'total' => $appointments->count(),
            'date' => now()->format('Y-m-d'),
        ], 200);
    }

    /**
     * Get upcoming approved appointments for the doctor (after today)
     */
    public function upcomingAppointments(Request $request)
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

        // Get upcoming approved appointments (after today)
        $appointments = Appointment::where('doctor_id', $doctor->doctor_id)
            ->where('status', 'Approved')
            ->whereDate('appointment_date', '>', now()->format('Y-m-d'))
            ->with(['patient.user', 'clinic'])
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->get()
            ->map(function ($appointment) {
                $dateTime = null;
                if ($appointment->appointment_date) {
                    $dateTime = Carbon::parse($appointment->appointment_date);

                    if ($appointment->appointment_time) {
                        $dateTime->setTimeFromTimeString($appointment->appointment_time);
                    }

                    $dateTime = $dateTime->toIso8601String();
                }

                return [
                    'id' => $appointment->appointment_id,
                    'dateTime' => $dateTime,
                    'status' => strtolower($appointment->status),
                    'notes' => $appointment->notes,
                    'patientName' => $appointment->patient?->user?->name ?? 'Unknown',
                    'patientPhone' => $appointment->patient?->user?->phone ?? null,
                    'clinicName' => $appointment->clinic?->name ?? null,
                    'clinicId' => $appointment->clinic_id,
                    'doctorId' => $appointment->doctor_id,
                    'patientId' => $appointment->patient_id,
                ];
            });

        return response()->json([
            'appointments' => $appointments,
            'total' => $appointments->count(),
            'from_date' => now()->addDay()->format('Y-m-d'),
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

            // Create next appointment if requested
            $nextAppointment = null;
            if ($request->input('create_next_appointment', false)) {
                $nextAppointment = $this->createNextAppointment($doctor, $appointment);
            }

            DB::commit();

            // Extract medical record from response
            $medicalRecordData = json_decode($medicalRecordResponse->getContent(), true);

            $response = [
                'message' => 'Appointment completed successfully',
                'appointment_id' => $appointment->appointment_id,
                'status' => $appointment->status,
                'medical_record_id' => $medicalRecordData['record']['record_id'] ?? null,
            ];

            if ($nextAppointment) {
                $response['next_appointment'] = [
                    'appointment_id' => $nextAppointment->appointment_id,
                    'appointment_date' => $nextAppointment->appointment_date,
                    'appointment_time' => $nextAppointment->appointment_time,
                    'status' => $nextAppointment->status,
                ];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to complete appointment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create next appointment with first available slot
     */
    private function createNextAppointment(Doctor $doctor, Appointment $completedAppointment)
    {
        // Start searching from tomorrow
        $searchDate = now()->addDay()->startOfDay();
        $maxDaysToSearch = 30; // Search up to 30 days ahead
        $daysSearched = 0;

        while ($daysSearched < $maxDaysToSearch) {
            $dateString = $searchDate->format('Y-m-d');

            // Get available slots for this date
            $availableSlots = $doctor->getAvailableSlots($dateString);

            // If there are available slots, book the first one
            if (!empty($availableSlots)) {
                $firstSlot = $availableSlots[0];

                // Create the appointment
                $nextAppointment = Appointment::create([
                    'patient_id' => $completedAppointment->patient_id,
                    'doctor_id' => $doctor->doctor_id,
                    'clinic_id' => $completedAppointment->clinic_id,
                    'appointment_date' => $dateString,
                    'appointment_time' => $firstSlot['start'],
                    'status' => 'Approved', // Auto-approve the next appointment
                    'notes' => 'Follow-up appointment (auto-scheduled)',
                ]);

                return $nextAppointment;
            }

            // Move to next day
            $searchDate->addDay();
            $daysSearched++;
        }

        // No available slots found within the search period
        return null;
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
