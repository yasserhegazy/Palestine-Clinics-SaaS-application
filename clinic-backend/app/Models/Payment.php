<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Payment extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'clinic_id',
        'received_by',
        'amount',
        'amount_paid',
        'payment_method',
        'status',
        'payment_date',
        'receipt_number',
        'notes',
        'exemption_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot method to generate receipt number
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = static::generateReceiptNumber($payment->clinic_id);
            }
        });
    }

    /**
     * Generate a unique receipt number
     */
    public static function generateReceiptNumber($clinicId): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));
        return "RCP-{$clinicId}-{$date}-{$random}";
    }

    /**
     * Get the appointment associated with this payment
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'appointment_id');
    }

    /**
     * Get the patient who made this payment
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Get the clinic that received this payment
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id', 'clinic_id');
    }

    /**
     * Get the user (secretary/reception) who received this payment
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'user_id');
    }

    /**
     * Check if payment is fully paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'Paid';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'Pending';
    }

    /**
     * Check if payment is exempt (charity/free)
     */
    public function isExempt(): bool
    {
        return $this->status === 'Exempt';
    }

    /**
     * Get remaining amount to be paid
     */
    public function getRemainingAmount(): float
    {
        return max(0, $this->amount - $this->amount_paid);
    }

    /**
     * Scope for filtering payments by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering payments by clinic
     */
    public function scopeByClinic($query, $clinicId)
    {
        return $query->where('clinic_id', $clinicId);
    }

    /**
     * Scope for filtering payments by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope for today's payments
     */
    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    /**
     * Scope for paid payments only
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    /**
     * Scope for pending payments only
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }
}
