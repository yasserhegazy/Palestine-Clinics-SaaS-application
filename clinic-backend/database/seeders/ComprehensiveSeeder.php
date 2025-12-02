<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Clinic;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Support\Facades\Hash;

class ComprehensiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * TESTING DATA STRUCTURE:
     * =======================
     * This seeder creates comprehensive test data for the clinic management system.
     * 
     * HARDCODED TEST USERS (Easy credentials for testing):
     * All passwords: "password"
     * 
     * 1. Platform Admin:
     *    - Email: admin@platform.com
     *    - Phone: +970590000000
     *    - No clinic association (platform-wide access)
     * 
     * 2. Test Clinic Users:
     *    Manager:
     *      - Email: manager@clinic.com, Phone: +970590000001
     *    
     *    Doctors:
     *      - Dr. One: doctor1@clinic.com, Phone: +970590000002
     *      - Dr. Two: doctor2@clinic.com, Phone: +970560000003
     *    
     *    Secretaries:
     *      - Secretary One: secretary1@clinic.com, Phone: +970590000004
     *      - Secretary Two: secretary2@clinic.com, Phone: +970560000005
     *    
     *    Patients:
     *      - Patient One: patient1@clinic.com, Phone: +970590000006
     *      - Patient Two: patient2@clinic.com, Phone: +970560000007
     * 
     * RANDOM DATA:
     * - 2 additional clinics with randomly generated data
     * - Each clinic has: 1 manager, 2 secretaries, 10 doctors, 15 patients
     * - Each patient has: 5 medical history records, 7 appointments (3 requested, 4 other statuses)
     * 
     * Total: 3 clinics, 8 hardcoded test users, extensive random data for realistic testing
     */
    public function run(): void
    {
        // 1. Create Platform Admin
        User::factory()->create([
            'clinic_id' => null,
            'name' => 'Platform Admin',
            'email' => 'admin@platform.com',
            'phone' => '+970590000000',
            'role' => 'Admin',
            'password_hash' => Hash::make('password'),
        ]);

        // 2. Create Test Clinic with Specific Users
        $testClinic = Clinic::factory()->create([
            'name' => 'Test Clinic',
            'status' => 'Active',
        ]);

        $this->command->info("Seeding data for Test Clinic: {$testClinic->name}");

        // Clinic Manager
        User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Clinic Manager',
            'email' => 'manager@clinic.com',
            'phone' => '+970590000001',
            'role' => 'Manager',
            'password_hash' => Hash::make('password'),
        ]);

        // Doctors
        $testDoctors = collect();
        
        // Doctor 1
        $doc1User = User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Dr. One',
            'email' => 'doctor1@clinic.com',
            'phone' => '+970590000002',
            'role' => 'Doctor',
            'password_hash' => Hash::make('password'),
        ]);
        $doc1 = Doctor::factory()->create(['user_id' => $doc1User->user_id]);
        $testDoctors->push($doc1);

        // Doctor 2
        $doc2User = User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Dr. Two',
            'email' => 'doctor2@clinic.com',
            'phone' => '+970560000003',
            'role' => 'Doctor',
            'password_hash' => Hash::make('password'),
        ]);
        $doc2 = Doctor::factory()->create(['user_id' => $doc2User->user_id]);
        $testDoctors->push($doc2);

        // Secretaries
        $testSecretaries = collect();

        // Secretary 1
        $sec1 = User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Secretary One',
            'email' => 'secretary1@clinic.com',
            'phone' => '+970590000004',
            'role' => 'Secretary',
            'password_hash' => Hash::make('password'),
        ]);
        $testSecretaries->push($sec1);

        // Secretary 2
        $sec2 = User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Secretary Two',
            'email' => 'secretary2@clinic.com',
            'phone' => '+970560000005',
            'role' => 'Secretary',
            'password_hash' => Hash::make('password'),
        ]);
        $testSecretaries->push($sec2);

        // Patients
        // Patient 1
        $pat1User = User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Patient One',
            'email' => 'patient1@clinic.com',
            'phone' => '+970590000006',
            'role' => 'Patient',
            'password_hash' => Hash::make('password'),
        ]);
        $pat1 = Patient::factory()->create(['user_id' => $pat1User->user_id]);
        $this->seedPatientData($testClinic, $pat1, $testDoctors, $testSecretaries);

        // Patient 2
        $pat2User = User::factory()->create([
            'clinic_id' => $testClinic->clinic_id,
            'name' => 'Patient Two',
            'email' => 'patient2@clinic.com',
            'phone' => '+970560000007',
            'role' => 'Patient',
            'password_hash' => Hash::make('password'),
        ]);
        $pat2 = Patient::factory()->create(['user_id' => $pat2User->user_id]);
        $this->seedPatientData($testClinic, $pat2, $testDoctors, $testSecretaries);

        // Create 2 more random clinics (total 3)
        $clinics = Clinic::factory()->count(2)->create();

        foreach ($clinics as $clinic) {
            $this->command->info("Seeding data for clinic: {$clinic->name}");

            // 1. Create 1 Manager
            User::factory()->create([
                'clinic_id' => $clinic->clinic_id,
                'role' => 'Manager',
                'password_hash' => Hash::make('password'),
            ]);

            // 2. Create 2 Secretaries
            $secretaries = User::factory()->count(2)->create([
                'clinic_id' => $clinic->clinic_id,
                'role' => 'Secretary',
                'password_hash' => Hash::make('password'),
            ]);

            // 3. Create 10 Doctors
            $doctors = collect();
            for ($i = 0; $i < 10; $i++) {
                $doctorUser = User::factory()->create([
                    'clinic_id' => $clinic->clinic_id,
                    'role' => 'Doctor',
                    'password_hash' => Hash::make('password'),
                ]);

                $doctor = Doctor::factory()->create([
                    'user_id' => $doctorUser->user_id,
                ]);
                
                $doctors->push($doctor);
            }

            // 4. Create 15 Patients
            for ($i = 0; $i < 15; $i++) {
                $patientUser = User::factory()->create([
                    'clinic_id' => $clinic->clinic_id,
                    'role' => 'Patient',
                    'password_hash' => Hash::make('password'),
                ]);

                $patient = Patient::factory()->create([
                    'user_id' => $patientUser->user_id,
                ]);

                $this->seedPatientData($clinic, $patient, $doctors, $secretaries);
            }
        }
    }

    private function seedPatientData($clinic, $patient, $doctors, $secretaries)
    {
        // 5. Create 5 Medical History records per patient
        MedicalRecord::factory()->count(5)->create([
            'patient_id' => $patient->patient_id,
            'doctor_id' => $doctors->random()->doctor_id,
        ]);

        // 6. Create 7 Appointments per patient
        // 3 Requested
        Appointment::factory()->count(3)->create([
            'clinic_id' => $clinic->clinic_id,
            'doctor_id' => $doctors->random()->doctor_id,
            'patient_id' => $patient->patient_id,
            'secretary_id' => null, // Requested usually doesn't have secretary yet
            'status' => 'Requested',
        ]);

        // 4 Other statuses (Approved, Completed, Cancelled)
        Appointment::factory()->count(4)->create([
            'clinic_id' => $clinic->clinic_id,
            'doctor_id' => $doctors->random()->doctor_id,
            'patient_id' => $patient->patient_id,
            'secretary_id' => $secretaries->random()->user_id,
            'status' => fake()->randomElement(['Approved', 'Completed', 'Cancelled']),
        ]);
    }
}
