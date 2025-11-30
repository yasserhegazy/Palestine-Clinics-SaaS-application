<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    //
    public function getAvailableDoctors()
    {
        return response()->json([
            'doctors' => Doctor::with('user')
                ->whereHas('user', function ($query) {
                    $query->where('clinic_id', auth()->user()->clinic_id);
                })
                ->get()
        ]);
    }

    public function createAppointmentForPatient()
    {
        
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
