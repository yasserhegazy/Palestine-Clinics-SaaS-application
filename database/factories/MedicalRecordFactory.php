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
            'visit_date' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'symptoms' => $this->faker->sentence(),
            'diagnosis' => $this->faker->sentence(),
            'prescription' => $this->faker->sentence(),
            'next_visit' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
        ];
    }
}
