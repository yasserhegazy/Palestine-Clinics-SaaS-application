<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Appointment\AvailableTimeSlotsRequest;
use App\Http\Requests\Clinic\Appointment\CreateAppointmentRequest;
use App\Http\Resources\Appointment\AppointmentResource;
use App\Http\Resources\Appointment\AvailableSlotsResource;
use App\Http\Resources\Doctor\DoctorSummaryResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Services\Clinic\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointmentService)
    {
    }

    public function getAvailableDoctors(Request $request): JsonResponse
    {
        $doctors = $this->appointmentService->getAvailableDoctorsForClinic($request->user()->clinic_id);

        return response()->json([
            'success' => true,
            'message' => 'Available doctors retrieved successfully.',
            'data' => DoctorSummaryResource::collection($doctors),
        ]);
    }

    public function createAppointmentForPatient(CreateAppointmentRequest $request): JsonResponse
    {
        $result = $this->appointmentService->createForPatient(
            $request->user(),
            $request->appointmentData()
        );

        return response()->json([
            'success' => true,
            'message' => 'Appointment created successfully.',
            'data' => [
                'appointment' => new AppointmentResource($result['appointment']),
                'payment' => $result['payment'] ? new PaymentResource($result['payment']) : null,
                'receipt_number' => $result['payment']?->receipt_number,
            ],
        ], 201);
    }

    public function getPatientHistory(Request $request, int $patientId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Patient appointment history retrieved successfully.',
            'data' => AppointmentResource::collection(
                $this->appointmentService->getPatientHistory($request->user(), $patientId)
            ),
        ]);
    }

    public function getAvailableTimeSlots(AvailableTimeSlotsRequest $request, int $id): JsonResponse
    {
        $payload = $this->appointmentService->getAvailableTimeSlots(
            $request->user(),
            $id,
            $request->requestedDate()
        );

        return response()->json([
            'success' => true,
            'message' => 'Available time slots retrieved successfully.',
            'data' => new AvailableSlotsResource($payload),
        ]);
    }
}
