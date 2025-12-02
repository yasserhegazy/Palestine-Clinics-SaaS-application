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
        $this->call(ComprehensiveSeeder::class);
    }
}


