<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'specialization' => fake()->randomElement(['Cardiology', 'Dermatology', 'Pediatrics', 'Neurology', 'Orthopedics', 'General Practice']),
            'available_days' => json_encode(fake()->randomElements(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], 3)),
            'clinic_room' => 'Room ' . fake()->numberBetween(100, 500),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'slot_duration' => 30,
        ];
    }
}
