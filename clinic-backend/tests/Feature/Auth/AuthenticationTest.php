<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected $clinic;
    protected $platformAdmin;
    protected $clinicManager;
    protected $doctor;
    protected $secretary;
    protected $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function seedTestData()
    {
        // Create a test clinic
        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'address' => '123 Test Street, Gaza, Palestine',
            'phone' => '+970123456789',
            'email' => 'test@clinic.ps',
            'subscription_plan' => 'Premium',
            'status' => 'Active'
        ]);

        // Create Platform Admin (SaaS Admin)
        $this->platformAdmin = User::create([
            'clinic_id' => null,
            'name' => 'Platform Admin',
            'email' => 'admin@platform.com',
            'phone' => '+970100000001',
            'password_hash' => Hash::make('admin123'),
            'role' => 'Admin',
            'status' => 'Active'
        ]);

        // Create Clinic Manager
        $this->clinicManager = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Clinic Manager',
            'email' => 'manager@clinic.ps',
            'phone' => '+970100000002',
            'password_hash' => Hash::make('manager123'),
            'role' => 'Manager',
            'status' => 'Active'
        ]);

        // Create Doctor
        $this->doctor = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Dr. Test Doctor',
            'email' => 'doctor@clinic.ps',
            'phone' => '+970100000003',
            'password_hash' => Hash::make('doctor123'),
            'role' => 'Doctor',
            'status' => 'Active'
        ]);

        // Create Secretary
        $this->secretary = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Test Secretary',
            'email' => 'secretary@clinic.ps',
            'phone' => '+970100000004',
            'password_hash' => Hash::make('secretary123'),
            'role' => 'Secretary',
            'status' => 'Active'
        ]);

        // Create Patient
        $this->patient = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Test Patient',
            'email' => 'patient@clinic.ps',
            'phone' => '+970100000005',
            'password_hash' => Hash::make('patient123'),
            'role' => 'Patient',
            'status' => 'Active'
        ]);

        // Create Suspended User
        User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Suspended User',
            'email' => 'suspended@clinic.ps',
            'phone' => '+970100000006',
            'password_hash' => Hash::make('suspended123'),
            'role' => 'Patient',
            'status' => 'Suspended'
        ]);
    }

    /** @test */
    public function platform_admin_can_login_successfully()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'admin123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'user_id', 'name', 'email', 'role', 'clinic_id'
                    ],
                    'token',
                    'role',
                    'is_platform_admin'
                ])
                ->assertJson([
                    'role' => 'Admin',
                    'is_platform_admin' => true
                ])
                ->assertJsonPath('user.clinic_id', null);
    }

    /** @test */
    public function clinic_manager_can_login_successfully()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@clinic.ps',
            'password' => 'manager123'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'user' => [
                        'user_id', 'name', 'email', 'role', 'clinic_id', 'clinic'
                    ],
                    'token',
                    'role',
                    'is_platform_admin'
                ])
                ->assertJson([
                    'role' => 'Manager',
                    'is_platform_admin' => false
                ])
                ->assertJsonPath('user.clinic.name', 'Test Clinic');
    }

    /** @test */
    public function doctor_can_login_successfully()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'doctor@clinic.ps',
            'password' => 'doctor123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'role' => 'Doctor',
                    'is_platform_admin' => false
                ])
                ->assertJsonPath('user.clinic.name', 'Test Clinic');
    }

    /** @test */
    public function secretary_can_login_successfully()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'secretary@clinic.ps',
            'password' => 'secretary123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'role' => 'Secretary',
                    'is_platform_admin' => false
                ]);
    }

    /** @test */
    public function patient_can_login_successfully()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'patient@clinic.ps',
            'password' => 'patient123'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'role' => 'Patient',
                    'is_platform_admin' => false
                ]);
    }

    /** @test */
    public function login_fails_with_wrong_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ]);
    }

    /** @test */
    public function login_fails_with_nonexistent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ]);
    }

    /** @test */
    public function login_fails_with_suspended_user()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'suspended@clinic.ps',
            'password' => 'suspended123'
        ]);

        $response->assertStatus(422)
                ->assertJson([
                    'message' => 'The provided credentials are incorrect.',
                    'errors' => [
                        'email' => ['The provided credentials are incorrect.']
                    ]
                ]);
    }

    /** @test */
    public function login_fails_with_missing_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'admin123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function login_fails_with_invalid_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'admin123'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function login_fails_with_missing_password()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['password']);
    }

    /** @test */
    public function login_fails_with_empty_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => ''
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        // Login first
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'admin123'
        ]);

        $token = $loginResponse->json('token');

        // Logout with token
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Successfully logged out'
                ]);
    }

    /** @test */
    public function logout_fails_without_token()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function logout_fails_with_invalid_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_roles_are_correctly_identified()
    {
        $testCases = [
            ['admin@platform.com', 'admin123', 'Admin', true],
            ['manager@clinic.ps', 'manager123', 'Manager', false],
            ['doctor@clinic.ps', 'doctor123', 'Doctor', false],
            ['secretary@clinic.ps', 'secretary123', 'Secretary', false],
            ['patient@clinic.ps', 'patient123', 'Patient', false],
        ];

        foreach ($testCases as [$email, $password, $expectedRole, $isPlatformAdmin]) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $email,
                'password' => $password
            ]);

            $response->assertStatus(200)
                    ->assertJson([
                        'role' => $expectedRole,
                        'is_platform_admin' => $isPlatformAdmin
                    ]);
        }
    }

    /** @test */
    public function clinic_users_have_clinic_data_in_response()
    {
        $clinicUsers = [
            'manager@clinic.ps',
            'doctor@clinic.ps',
            'secretary@clinic.ps',
            'patient@clinic.ps'
        ];

        foreach ($clinicUsers as $email) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $email,
                'password' => substr($email, 0, strpos($email, '@')) . '123'
            ]);

            $response->assertStatus(200)
                    ->assertJsonStructure([
                        'user' => ['clinic']
                    ])
                    ->assertJsonPath('user.clinic.name', 'Test Clinic');
        }
    }

    /** @test */
    public function platform_admin_does_not_have_clinic_data()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'admin123'
        ]);

        $response->assertStatus(200)
                ->assertJsonPath('user.clinic_id', null)
                ->assertJsonMissing(['user.clinic']);
    }

    /** @test */
    public function tokens_are_unique_per_user_role()
    {
        $users = [
            ['admin@platform.com', 'admin123'],
            ['manager@clinic.ps', 'manager123'],
            ['doctor@clinic.ps', 'doctor123'],
        ];

        $tokens = [];

        foreach ($users as [$email, $password]) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $email,
                'password' => $password
            ]);

            $token = $response->json('token');
            $this->assertNotContains($token, $tokens, 'Token should be unique');
            $tokens[] = $token;
        }
    }
}
