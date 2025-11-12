<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a demo clinic
        $clinic = Clinic::create([
            'name' => 'Demo Clinic',
            'address' => '123 Main Street, Gaza, Palestine',
            'phone' => '+970123456789',
            'email' => 'info@democlinic.ps',
            'subscription_plan' => 'Premium',
            'status' => 'Active'
        ]);

        // Create SaaS Platform Admin (no clinic_id - manages whole platform)
        User::create([
            'clinic_id' => null, // Platform admin doesn't belong to any specific clinic
            'name' => 'Platform Admin',
            'email' => 'admin@platform.com',
            'phone' => '+970100000001',
            'password_hash' => Hash::make('admin123'),
            'role' => 'Admin',
            'status' => 'Active'
        ]);

        // Create Clinic Manager for the demo clinic
        User::create([
            'clinic_id' => $clinic->clinic_id,
            'name' => 'Clinic Manager',
            'email' => 'manager@clinic.ps',
            'phone' => '+970100000002',
            'password_hash' => Hash::make('manager123'),
            'role' => 'Manager',
            'status' => 'Active'
        ]);

        // Create a doctor user
        $doctorUser = User::create([
            'clinic_id' => $clinic->clinic_id,
            'name' => 'Dr. Ahmad Hassan',
            'email' => 'doctor@clinic.ps',
            'phone' => '+970100000003',
            'password_hash' => Hash::make('doctor123'),
            'role' => 'Doctor',
            'status' => 'Active'
        ]);

        // Create a patient user
        $patientUser = User::create([
            'clinic_id' => $clinic->clinic_id,
            'name' => 'Omar Ali',
            'email' => 'patient@clinic.ps',
            'phone' => '+970100000005',
            'password_hash' => Hash::make('patient123'),
            'role' => 'Patient',
            'status' => 'Active'
        ]);

        // Create secretary user
        User::create([
            'clinic_id' => $clinic->clinic_id,
            'name' => 'Sara Mohammed',
            'email' => 'secretary@clinic.ps',
            'phone' => '+970100000004',
            'password_hash' => Hash::make('secretary123'),
            'role' => 'Secretary',
            'status' => 'Active'
        ]);
    }
}


