<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $primaryKey = 'clinic_id';

    protected $fillable = [
        'name',
        'speciality',
        'address',
        'phone',
        'email',
        'logo',
        'subscription_plan',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['logo_url'];

    /**
     * Get the full URL for the clinic logo
     */
    public function getLogoUrlAttribute(): ?string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }
        return null;
    }

    /**
     * Get all users belonging to this clinic
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_id', 'clinic_id');
    }

    /**
     * Get all appointments for this clinic
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'clinic_id', 'clinic_id');
    }

    /**
     * Get all doctors in this clinic
     */
    public function doctors(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_id', 'clinic_id')
                    ->where('role', 'Doctor');
    }

    /**
     * Get all patients in this clinic
     */
    public function patients(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_id', 'clinic_id')
                    ->where('role', 'Patient');
    }

    /**
     * Get all secretaries in this clinic
     */
    public function secretaries(): HasMany
    {
        return $this->hasMany(User::class, 'clinic_id', 'clinic_id')
                    ->where('role', 'Secretary');
    }
}
