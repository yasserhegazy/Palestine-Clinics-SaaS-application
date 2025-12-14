<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'clinic_id',
        'name',
        'email',
        'phone',
        'password_hash',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_hash' => 'hashed',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the clinic that owns the user (nullable for platform admins)
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class, 'clinic_id', 'clinic_id');
    }

    /**
     * Get the patient record for this user (if role is Patient)
     */
    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class, 'user_id', 'user_id');
    }

    /**
     * Get the doctor record for this user (if role is Doctor)
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class, 'user_id', 'user_id');
    }

    /**
     * Get appointments where this user is the secretary
     */
    public function secretaryAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'secretary_id', 'user_id');
    }

    /**
     * Check if user is a doctor
     */
    public function isDoctor(): bool
    {
        return $this->role === 'Doctor';
    }

    /**
     * Check if user is a patient
     */
    public function isPatient(): bool
    {
        return $this->role === 'Patient';
    }

    /**
     * Check if user is a secretary
     */
    public function isSecretary(): bool
    {
        return $this->role === 'Secretary';
    }

    /**
     * Check if user is a platform admin (SaaS admin)
     */
    public function isPlatformAdmin(): bool
    {
        return $this->role === 'Admin' && is_null($this->clinic_id);
    }

    /**
     * Check if user is a clinic manager
     */
    public function isClinicManager(): bool
    {
        return $this->role === 'Manager';
    }

    /**
     * Check if user belongs to a specific clinic
     */
    public function belongsToClinic(): bool
    {
        return !is_null($this->clinic_id);
    }

    /**
     * Override getAuthPassword to use password_hash field
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }
}
