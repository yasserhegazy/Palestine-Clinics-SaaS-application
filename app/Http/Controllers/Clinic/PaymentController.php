<?php

namespace App\Http\Controllers\Clinic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Clinic\Payment\DailyPaymentReportRequest;
use App\Http\Requests\Clinic\Payment\PaymentIndexRequest;
use App\Http\Requests\Clinic\Payment\StorePaymentRequest;
use App\Http\Requests\Clinic\Payment\UpdatePaymentRequest;
use App\Http\Resources\Payment\PaymentCollectionResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Services\Clinic\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    /**
     * Create a new payment for an appointment.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = $this->paymentService->store($request->user(), $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => [
                'payment' => new PaymentResource($payment),
                'receipt_number' => $payment->receipt_number,
            ],
        ], 201);
    }

    /**
     * Get payment details by ID.
     */
    public function show(Request $request, int $payment_id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully.',
            'data' => new PaymentResource(
                $this->paymentService->show($request->user(), $payment_id)
            ),
        ], 200);
    }

    /**
     * Get all payments for the clinic.
     */
    public function index(PaymentIndexRequest $request): JsonResponse
    {
        $payments = $this->paymentService->list(
            $request->user(),
            $request->filters(),
            (int) ($request->validated()['per_page'] ?? 20)
        );

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully.',
            'data' => [
                'payments' => PaymentResource::collection($payments),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                ],
            ],
        ], 200);
    }

    /**
     * Get pending payments for the clinic.
     */
    public function pendingPayments(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Pending payments retrieved successfully.',
            'data' => $this->paymentService->pending($request->user()),
        ], 200);
    }

    /**
     * Get daily payment report.
     */
    public function dailyReport(DailyPaymentReportRequest $request): JsonResponse
    {
        $report = $this->paymentService->dailyReport($request->user(), $request->reportDate());

        return response()->json([
            'success' => true,
            'message' => 'Daily payments report retrieved successfully.',
            'data' => $report,
        ], 200);
    }

    /**
     * Update payment (e.g., mark pending as paid).
     */
    public function update(UpdatePaymentRequest $request, int $payment_id): JsonResponse
    {
        $payment = $this->paymentService->update($request->user(), $payment_id, $request->payload());

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => new PaymentResource($payment),
        ], 200);
    }

    /**
     * Get patient payment history.
     */
    public function patientHistory(Request $request, int $patient_id): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Patient payment history retrieved successfully.',
            'data' => $this->paymentService->patientHistory($request->user(), $patient_id),
        ], 200);
    }
}
