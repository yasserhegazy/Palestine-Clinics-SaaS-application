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

        // Create doctor record
        $doctor = \App\Models\Doctor::create([
            'user_id' => $doctorUser->user_id,
            'specialization' => 'Cardiology',
            'available_days' => json_encode(['Monday', 'Wednesday', 'Friday']),
            'clinic_room' => 'Room 101',
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

        // Create patient record
        $patient = \App\Models\Patient::create([
            'user_id' => $patientUser->user_id,
            'national_id' => '123456789',
            'date_of_birth' => '1990-05-15',
            'gender' => 'Male',
            'address' => 'Gaza City, Palestine',
            'blood_type' => 'A+',
            'allergies' => null,
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

        // Create sample appointments for the patient
        \App\Models\Appointment::create([
            'clinic_id' => $clinic->clinic_id,
            'doctor_id' => $doctor->doctor_id,
            'patient_id' => $patient->patient_id,
            'secretary_id' => null,
            'appointment_date' => now()->addDays(7)->setTime(9, 30),
            'status' => 'Approved',
            'notes' => 'Regular checkup',
        ]);

        \App\Models\Appointment::create([
            'clinic_id' => $clinic->clinic_id,
            'doctor_id' => $doctor->doctor_id,
            'patient_id' => $patient->patient_id,
            'secretary_id' => null,
            'appointment_date' => now()->addDays(14)->setTime(11, 0),
            'status' => 'Requested',
            'notes' => 'Follow-up appointment',
        ]);

        \App\Models\Appointment::create([
            'clinic_id' => $clinic->clinic_id,
            'doctor_id' => $doctor->doctor_id,
            'patient_id' => $patient->patient_id,
            'secretary_id' => null,
            'appointment_date' => now()->subDays(10)->setTime(16, 0),
            'status' => 'Completed',
            'notes' => 'Initial consultation',
        ]);
        // Create medical history for the patient
        \App\Models\MedicalRecord::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctor->doctor_id,
            'visit_date' => now()->subMonths(3)->subDays(5),
            'symptoms' => 'Persistent skin rash and itching',
            'diagnosis' => 'Chronic skin allergy',
            'prescription' => 'Antihistamines and topical cream',
            'next_visit' => now()->subMonths(2),
        ]);

        \App\Models\MedicalRecord::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctor->doctor_id,
            'visit_date' => now()->subMonths(6),
            'symptoms' => 'Blurred vision and headaches',
            'diagnosis' => 'Mild myopia',
            'prescription' => 'Prescription glasses',
            'next_visit' => null,
        ]);

        \App\Models\MedicalRecord::create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctor->doctor_id,
            'visit_date' => now()->subYear(),
            'symptoms' => 'Toothache and sensitivity',
            'diagnosis' => 'Tooth decay',
            'prescription' => 'Dental filling and painkillers',
            'next_visit' => null,
        ]);
    }
}


