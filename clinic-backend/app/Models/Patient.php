<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $primaryKey = 'patient_id';

    protected $fillable = [
        'user_id',
        'national_id',
        'date_of_birth',
        'gender',
        'address',
        'blood_type',
        'allergies',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user record associated with the patient
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the clinic through the user relationship
     */
    public function clinic()
    {
        return $this->user->clinic();
    }

    /**
     * Get all appointments for this patient
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id', 'patient_id');
    }

    /**
     * Get all medical records for this patient
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'patient_id', 'patient_id');
    }

    /**
     * Get the patient's full name through user relationship
     */
    public function getFullNameAttribute(): string
    {
        return $this->user->name;
    }

    /**
     * Get the patient's email through user relationship
     */
    public function getEmailAttribute(): string
    {
        return $this->user->email;
    }

    /**
     * Get the patient's phone through user relationship
     */
    public function getPhoneAttribute(): string
    {
        return $this->user->phone;
    }
}
