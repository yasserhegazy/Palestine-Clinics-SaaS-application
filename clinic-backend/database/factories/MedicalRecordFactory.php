<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MedicalRecord>
 */
class MedicalRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_date' => fake()->dateTimeBetween('-2 years', 'now'),
            'symptoms' => fake()->sentence(),
            'diagnosis' => fake()->sentence(),
            'prescription' => fake()->sentence(),
            'next_visit' => fake()->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }
}
