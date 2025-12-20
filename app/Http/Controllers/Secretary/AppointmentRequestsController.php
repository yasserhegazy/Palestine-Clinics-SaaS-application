<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\Appointments\RejectAppointmentRequest;
use App\Http\Requests\Secretary\Appointments\RescheduleAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Services\Secretary\SecretaryAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentRequestsController extends Controller
{
    public function __construct(private readonly SecretaryAppointmentService $appointmentService)
    {
    }

    /**
     * Get all pending appointment requests for the clinic.
     */
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

    /**
     * Approve an appointment request.
     */
    public function approve(Request $request, int $appointment_id): JsonResponse
    {
        $appointment = $this->appointmentService->approve($request->user(), $appointment_id);

        return response()->json([
            'success' => true,
            'message' => 'Appointment request approved successfully.',
            'data' => new AppointmentResource($appointment),
        ], 200);
    }

    /**
     * Reject an appointment request.
     */
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

    /**
     * Reschedule an appointment request.
     */
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
