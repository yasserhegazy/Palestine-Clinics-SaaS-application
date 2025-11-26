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
}
