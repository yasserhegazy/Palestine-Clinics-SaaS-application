<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\Appointments\RejectAppointmentRequest;
use App\Http\Requests\Doctor\Appointments\RescheduleAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Services\Doctor\DoctorAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentRequestsController extends Controller
{
    public function __construct(private readonly DoctorAppointmentService $appointmentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Appointment requests retrieved successfully.',
            'data' => AppointmentResource::collection(
                $this->appointmentService->requests($request->user())
            ),
        ], 200);
    }

    public function approve(Request $request, int $appointment_id): JsonResponse
    {
        $appointment = $this->appointmentService->approve($request->user(), $appointment_id);

        return response()->json([
            'success' => true,
            'message' => 'Appointment request approved successfully.',
            'data' => new AppointmentResource($appointment),
        ], 200);
    }
    
    public function reject(RejectAppointmentRequest $request, int $appointment_id): JsonResponse
    {
        $appointment = $this->appointmentService->reject(
            $request->user(),
            $appointment_id,
            $request->reason()
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment request rejected successfully.',
            'data' => new AppointmentResource($appointment),
        ], 200);
    }

    public function reschedule(RescheduleAppointmentRequest $request, int $appointment_id): JsonResponse
    {
        $appointment = $this->appointmentService->reschedule(
            $request->user(),
            $appointment_id,
            $request->payload()
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment rescheduled successfully.',
            'data' => new AppointmentResource($appointment),
        ], 200);
    }
}
