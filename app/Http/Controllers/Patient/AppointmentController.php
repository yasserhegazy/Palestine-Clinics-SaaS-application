<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\Appointment\StorePatientAppointmentRequest;
use App\Http\Requests\Patient\Appointment\UpdatePatientAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Doctor\DoctorSummaryResource;
use App\Services\Patient\PatientAppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly PatientAppointmentService $appointmentService)
    {
    }

    /**
     * Create a new appointment request by patient
     */
    public function createAppointment(StorePatientAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointmentService->create($request->user(), $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Appointment request created successfully. You will be notified when it is approved.',
            'data' => new AppointmentResource($appointment),
        ], 201);
    }

    /**
     * Get all appointments for the authenticated patient
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Appointments retrieved successfully.',
            'data' => AppointmentResource::collection(
                $this->appointmentService->list($request->user())
            ),
        ], 200);
    }

    /**
     * Get upcoming appointments for the authenticated patient
     */
    public function upcoming(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Upcoming appointments retrieved successfully.',
            'data' => AppointmentResource::collection(
                $this->appointmentService->upcoming($request->user())
            ),
        ], 200);
    }

    /**
     * Get a specific appointment
     */
    public function show(Request $request, int $appointment_id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Appointment retrieved successfully.',
            'data' => new AppointmentResource(
                $this->appointmentService->show($request->user(), $appointment_id)
            ),
        ], 200);
    }

    /**
     * Update an appointment (only for pending appointments)
     */
    public function update(UpdatePatientAppointmentRequest $request, int $appointment_id): JsonResponse
    {
        $appointment = $this->appointmentService->update(
            $request->user(),
            $appointment_id,
            $request->payload()
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment updated successfully.',
            'data' => new AppointmentResource($appointment),
        ], 200);
    }

    /**
     * Cancel an appointment
     */
    public function cancel(Request $request, int $appointment_id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Appointment cancelled successfully.',
            'data' => new AppointmentResource(
                $this->appointmentService->cancel($request->user(), $appointment_id)
            ),
        ], 200);
    }

    /**
     * Get available doctors for the patient's clinic
     */
    public function getAvailableDoctors(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Available doctors retrieved successfully.',
            'data' => DoctorSummaryResource::collection(
                $this->appointmentService->availableDoctors($request->user())
            ),
        ], 200);
    }
}
