<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicalRecord extends Model
{
    protected $primaryKey = 'record_id';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'visit_date',
        'symptoms',
        'diagnosis',
        'prescription',
        'next_visit',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
        'next_visit' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the patient that owns the medical record
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    /**
     * Get the doctor who created the medical record
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id', 'doctor_id');
    }

    /**
     * Get the clinic through the patient relationship
     */
    public function clinic()
    {
        return $this->patient->user->clinic();
    }

    /**
     * Scope for filtering records by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('visit_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering records by patient
     */
    public function scopeForPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    /**
     * Scope for filtering records by doctor
     */
    public function scopeByDoctor($query, $doctorId)
    {
        return $query->where('doctor_id', $doctorId);
    }

    /**
     * Scope for recent records
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('visit_date', '>=', now()->subDays($days));
    }

    /**
     * Check if there's a next visit scheduled
     */
    public function hasNextVisit(): bool
    {
        return !is_null($this->next_visit);
    }

    /**
     * Check if next visit is overdue
     */
    public function isNextVisitOverdue(): bool
    {
        return $this->hasNextVisit() && $this->next_visit < today();
    }
}
