<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DailyReportController extends Controller
{
    /**
     * Get daily payment report for secretary
     */
    public function dailyReport(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $date = $request->get('date', today()->format('Y-m-d'));

        $payments = Payment::where('clinic_id', $user->clinic_id)
            ->whereDate('payment_date', $date)
            ->with(['patient.user', 'appointment.doctor.user', 'receiver'])
            ->get();


        $summary = [
            'date' => $date,
            'clinic_id' => $user->clinic_id,
            'total_collected' => $payments->where('status', 'Paid')->sum('amount_paid'),
            'total_partial' => $payments->where('status', 'Partial')->sum('amount_paid'),
            'total_exempt' => $payments->where('status', 'Exempt')->count(),
            'total_pending' => $payments->where('status', 'Pending')->sum('amount'),
            'cash_count' => $payments->where('payment_method', 'Cash')->count(),
            'exempt_count' => $payments->where('payment_method', 'Exempt')->count(),
            'total_transactions' => $payments->count(),
        ];

        // Group by receiver (who collected)
        $byReceiver = $payments->groupBy('received_by')->map(function ($group) {
            return [
                'receiver_name' => $group->first()->receiver?->name ?? 'Unknown',
                'total_collected' => $group->sum('amount_paid'),
                'transaction_count' => $group->count(),
            ];
        })->values();

        return response()->json([
            'summary' => $summary,
            'by_receiver' => $byReceiver,
            'payments' => $payments,
        ], 200);
    }

    /**
     * Get appointments summary for the day
     */
    public function appointmentsSummary(Request $request)
    {
        $user = $request->user();

        if (!$user->clinic_id) {
            return response()->json([
                'message' => 'You are not associated with any clinic',
            ], 403);
        }

        $date = $request->get('date', today()->format('Y-m-d'));

        $appointments = Appointment::where('clinic_id', $user->clinic_id)
            ->whereDate('appointment_date', $date)
            ->with(['patient.user', 'doctor.user'])
            ->get();

        $summary = [
            'date' => $date,
            'clinic_id' => $user->clinic_id,
            'total_appointments' => $appointments->count(),
            'completed' => $appointments->where('status', 'Completed')->count(),
            'pending' => $appointments->where('status', 'Pending')->count(),
            'approved' => $appointments->where('status', 'Approved')->count(),
            'cancelled' => $appointments->where('status', 'Cancelled')->count(),
            'rejected' => $appointments->where('status', 'Rejected')->count(),
        ];

        // Group by doctor
        $byDoctor = $appointments->groupBy('doctor_id')->map(function ($group) {
            return [
                'doctor_name' => $group->first()->doctor?->user?->name ?? 'Unknown',
                'appointment_count' => $group->count(),
                'completed' => $group->where('status', 'Completed')->count(),
            ];
        })->values();

        return response()->json([
            'summary' => $summary,
            'by_doctor' => $byDoctor,
            'appointments' => $appointments,
        ], 200);
    }
}
