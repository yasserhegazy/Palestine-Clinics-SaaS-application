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
        // Check if start_time and end_time are set
        if (!$this->start_time || !$this->end_time) {
            // Return default working hours if not set
            $this->start_time = '09:00';
            $this->end_time = '17:00';
        }

        // Get working hours
        $workingTime = [
            'start' => is_string($this->start_time) ? $this->start_time : $this->start_time->format('H:i'),
            'end' => is_string($this->end_time) ? $this->end_time : $this->end_time->format('H:i'),
        ];

        // Get booked appointments for this date
        $bookedAppointments = $this->appointments()
            ->whereDate('appointment_date', $date)
            ->whereIn('status', ['Requested', 'Pending Doctor Approval', 'Approved'])
            ->get()
            ->map(function ($appointment) {
                $startTime = \Carbon\Carbon::parse($appointment->appointment_date);
                $endTime = $startTime->copy()->addMinutes($this->slot_duration ?? 30);
                
                return [
                    'start' => $startTime->format('H:i'),
                    'end' => $endTime->format('H:i'),
                ];
            })
            ->toArray();

        // Use TimeSlotGenerator to get available slots
        $generator = new \App\Helpers\TimeSlotGenerator(
            $workingTime,
            $this->slot_duration ?? 30,
            $bookedAppointments
        );

        $availableSlotTimes = $generator->get_final_available_slots();

        // Convert to format expected by frontend (array of objects with start and end)
        return array_map(function ($startTime) {
            $startMinutes = $this->convertTimeToMinutes($startTime);
            $endMinutes = $startMinutes + ($this->slot_duration ?? 30);
            $endTime = $this->convertMinutesToTime($endMinutes);
            
            return [
                'start' => $startTime,
                'end' => $endTime,
            ];
        }, $availableSlotTimes);
    }

    /**
     * Convert time string to minutes
     */
    private function convertTimeToMinutes(string $time): int
    {
        $parts = explode(":", $time);
        $hours = (int)$parts[0]; 
        $minutes = (int)$parts[1];
        return $hours * 60 + $minutes;
    }

    /**
     * Convert minutes to time string
     */
    private function convertMinutesToTime(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $timeMinutes = $minutes % 60;
        
        return sprintf('%02d:%02d', $hours, $timeMinutes);
    }
}
