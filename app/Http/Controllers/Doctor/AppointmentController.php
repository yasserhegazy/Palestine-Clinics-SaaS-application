<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\Appointments\AppointmentIndexRequest;
use App\Http\Requests\Doctor\Appointments\CompleteAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\MedicalRecord\MedicalRecordResource;
use App\Services\Doctor\DoctorAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly DoctorAppointmentService $appointmentService)
    {
    }

    /**
     * Get today's approved appointments for the doctor
     */
    public function todayAppointments(Request $request): JsonResponse
    {
        $result = $this->appointmentService->todayAppointments($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Today appointments retrieved successfully.',
            'data' => [
                'appointments' => AppointmentResource::collection($result['appointments']),
                'total' => $result['appointments']->count(),
                'date' => now()->format('Y-m-d'),
            ],
        ], 200);
    }

    /**
     * Get upcoming approved appointments for the doctor (after today)
     */
    public function upcomingAppointments(Request $request): JsonResponse
    {
        $result = $this->appointmentService->upcomingAppointments($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Upcoming appointments retrieved successfully.',
            'data' => [
                'appointments' => AppointmentResource::collection($result['appointments']),
                'total' => $result['appointments']->count(),
                'from_date' => now()->addDay()->format('Y-m-d'),
            ],
        ], 200);
    }

    /**
     * Complete an appointment and create a medical record
     */
    public function completeAppointment(CompleteAppointmentRequest $request, int $appointment_id): JsonResponse
    {
        $result = $this->appointmentService->complete(
            $request->user(),
            $appointment_id,
            $request->payload()
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment completed successfully.',
            'data' => [
                'appointment' => new AppointmentResource($result['appointment']),
                'medical_record' => new MedicalRecordResource($result['medical_record']),
                'next_appointment' => $result['next_appointment']
                    ? new AppointmentResource($result['next_appointment'])
                    : null,
            ],
        ], 201);
    }

    /**
     * Get all appointments for the doctor (optional: with filters)
     */
    public function index(AppointmentIndexRequest $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Appointments retrieved successfully.',
            'data' => AppointmentResource::collection(
                $this->appointmentService->list($request->user(), $request->filters())
            ),
        ], 200);
    }
}
