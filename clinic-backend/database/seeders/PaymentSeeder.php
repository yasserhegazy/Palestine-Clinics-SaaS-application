<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some existing appointments to create payments for
        $appointments = Appointment::with(['patient', 'clinic'])->take(10)->get();
        
        if ($appointments->isEmpty()) {
            $this->command->info('No appointments found. Please run AppointmentSeeder first.');
            return;
        }

        // Get a secretary/reception user to assign as receiver
        $secretary = User::where('role', 'Secretary')->first();
        
        foreach ($appointments as $appointment) {
            Payment::create([
                'appointment_id' => $appointment->appointment_id,
                'patient_id' => $appointment->patient_id,
                'clinic_id' => $appointment->clinic_id,
                'received_by' => $secretary?->user_id,
                'amount' => fake()->randomFloat(2, 50, 500),
                'amount_paid' => fake()->randomFloat(2, 50, 500),
                'payment_method' => fake()->randomElement(['Cash', 'Later', 'Partial', 'Exempt']),
                'status' => fake()->randomElement(['Paid', 'Pending', 'Partial', 'Exempt']),
                'payment_date' => fake()->dateTimeBetween('-7 days', 'now'),
                'notes' => fake()->optional()->sentence(),
                'exemption_reason' => fake()->optional()->sentence(),
            ]);
        }

        // Create some payments for today specifically
        $todayAppointments = $appointments->take(3);
        foreach ($todayAppointments as $appointment) {
            Payment::create([
                'appointment_id' => $appointment->appointment_id,
                'patient_id' => $appointment->patient_id,
                'clinic_id' => $appointment->clinic_id,
                'received_by' => $secretary?->user_id,
                'amount' => fake()->randomFloat(2, 100, 300),
                'amount_paid' => fake()->randomFloat(2, 100, 300),
                'payment_method' => fake()->randomElement(['Cash', 'Exempt']),
                'status' => fake()->randomElement(['Paid', 'Exempt']),
                'payment_date' => now(),
                'notes' => 'Today\'s payment for testing',
            ]);
        }

        $this->command->info('Payment seeder completed successfully.');
    }
}