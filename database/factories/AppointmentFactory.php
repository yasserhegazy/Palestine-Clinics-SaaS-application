<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 month', '+1 month');
        return [
            'appointment_date' => $date->format('Y-m-d'),
            'appointment_time' => $date->format('H:i:00'),
            'status' => $this->faker->randomElement(['Requested', 'Pending Doctor Approval', 'Approved', 'Cancelled', 'Completed']),
            'notes' => $this->faker->sentence(),
        ];
    }
}
