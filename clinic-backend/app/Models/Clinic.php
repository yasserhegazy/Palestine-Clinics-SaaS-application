<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinic extends Model
{
    protected $primaryKey = 'clinic_id';

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'subscription_plan',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
