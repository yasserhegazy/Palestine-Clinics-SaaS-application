<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Helpers\TimeSlotGenerator;

class Doctor extends Model
{
    protected $primaryKey = 'doctor_id';

    protected $fillable = [
        'user_id',
        'specialization',
        'available_days',
        'clinic_room',
        'start_time',
        'end_time',
        'slot_duration',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    /**
     * Get the user record associated with the doctor
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
     * Get all appointments for this doctor
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id', 'doctor_id');
    }

    /**
     * Get all medical records created by this doctor
     */
    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class, 'doctor_id', 'doctor_id');
    }

    /**
     * Get the doctor's full name through user relationship
     */
    public function getFullNameAttribute(): string
    {
        return $this->user->name;
    }

    /**
     * Get the doctor's email through user relationship
     */
    public function getEmailAttribute(): string
    {
        return $this->user->email;
    }

    /**
     * Get the doctor's phone through user relationship
     */
    public function getPhoneAttribute(): string
    {
        return $this->user->phone;
    }

    /**
     * Get upcoming appointments for this doctor
     */
    public function upcomingAppointments(): HasMany
    {
        return $this->appointments()
                    ->where('appointment_date', '>', now())
                    ->where('status', '!=', 'Cancelled')
                    ->orderBy('appointment_date');
    }

    /**
     * Get today's appointments for this doctor
     */
    public function todayAppointments(): HasMany
    {
        return $this->appointments()
                    ->whereDate('appointment_date', today())
                    ->where('status', '!=', 'Cancelled')
                    ->orderBy('appointment_date');
    }

    /**
     * Get available time slots for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @return array Available time slots
     */
    public function getAvailableSlots(string $date): array
    {
        // Get working hours
        $workingTime = [
            'start' => $this->start_time->format('H:i'),
            'end' => $this->end_time->format('H:i'),
        ];

        // Get booked appointments for this date
        $bookedAppointments = $this->appointments()
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->get()
            ->map(function ($appointment) {
                $startTime = \Carbon\Carbon::parse($appointment->appointment_time);
                $endTime = $startTime->copy()->addMinutes($this->slot_duration);
                
                return [
                    'start' => $startTime->format('H:i'),
                    'end' => $endTime->format('H:i'),
                ];
            })
            ->toArray();

        // Use TimeSlotGenerator to get available slots
        $generator = new \App\Helpers\TimeSlotGenerator(
            $workingTime,
            $this->slot_duration,
            $bookedAppointments
        );

        return $generator->get_final_available_slots();
    }
}
