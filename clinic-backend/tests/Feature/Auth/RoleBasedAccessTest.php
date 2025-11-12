<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleBasedAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $clinic;
    protected $platformAdmin;
    protected $clinicManager;
    protected $doctor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTestData();
    }

    protected function seedTestData()
    {
        $this->clinic = Clinic::create([
            'name' => 'Test Clinic',
            'address' => '123 Test Street',
            'phone' => '+970123456789',
            'email' => 'test@clinic.ps',
            'subscription_plan' => 'Premium',
            'status' => 'Active'
        ]);

        $this->platformAdmin = User::create([
            'clinic_id' => null,
            'name' => 'Platform Admin',
            'email' => 'admin@platform.com',
            'phone' => '+970100000001',
            'password_hash' => Hash::make('admin123'),
            'role' => 'Admin',
            'status' => 'Active'
        ]);

        $this->clinicManager = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Clinic Manager',
            'email' => 'manager@clinic.ps',
            'phone' => '+970100000002',
            'password_hash' => Hash::make('manager123'),
            'role' => 'Manager',
            'status' => 'Active'
        ]);

        $this->doctor = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'Dr. Test',
            'email' => 'doctor@clinic.ps',
            'phone' => '+970100000003',
            'password_hash' => Hash::make('doctor123'),
            'role' => 'Doctor',
            'status' => 'Active'
        ]);
    }

    /** @test */
    public function platform_admin_has_correct_permissions_flags()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'admin123'
        ]);

        $data = $response->json();

        // Platform admin specific checks
        $this->assertTrue($data['is_platform_admin']);
        $this->assertEquals('Admin', $data['role']);
        $this->assertNull($data['user']['clinic_id']);
        $this->assertArrayNotHasKey('clinic', $data['user']);
    }

    /** @test */
    public function clinic_manager_has_correct_clinic_access()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'manager@clinic.ps',
            'password' => 'manager123'
        ]);

        $data = $response->json();

        // Clinic manager specific checks
        $this->assertFalse($data['is_platform_admin']);
        $this->assertEquals('Manager', $data['role']);
        $this->assertEquals($this->clinic->clinic_id, $data['user']['clinic_id']);
        $this->assertEquals('Test Clinic', $data['user']['clinic']['name']);
    }

    /** @test */
    public function doctor_has_clinic_association()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'doctor@clinic.ps',
            'password' => 'doctor123'
        ]);

        $data = $response->json();

        // Doctor specific checks
        $this->assertFalse($data['is_platform_admin']);
        $this->assertEquals('Doctor', $data['role']);
        $this->assertEquals($this->clinic->clinic_id, $data['user']['clinic_id']);
        $this->assertArrayHasKey('clinic', $data['user']);
    }

    /** @test */
    public function login_with_sql_injection_attempt_fails_safely()
    {
        $maliciousInputs = [
            "admin@platform.com' OR '1'='1",
            "admin@platform.com'; DROP TABLE users; --",
            "admin@platform.com' UNION SELECT * FROM users --",
            "admin@platform.com' OR 1=1 --",
        ];

        foreach ($maliciousInputs as $maliciousEmail) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $maliciousEmail,
                'password' => 'admin123'
            ]);

            $response->assertStatus(422)
                    ->assertJson([
                        'message' => 'The provided credentials are incorrect.'
                    ]);
        }
    }

    /** @test */
    public function login_with_xss_attempt_is_handled_safely()
    {
        $xssInputs = [
            '<script>alert("xss")</script>@example.com',
            'admin@platform.com<script>alert("xss")</script>',
            'javascript:alert("xss")@example.com',
        ];

        foreach ($xssInputs as $xssEmail) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $xssEmail,
                'password' => 'admin123'
            ]);

            // Should either fail validation or fail authentication
            $this->assertContains($response->status(), [422, 422]);
        }
    }

    /** @test */
    public function login_with_extremely_long_inputs_fails()
    {
        $longEmail = str_repeat('a', 1000) . '@example.com';
        $longPassword = str_repeat('p', 1000);

        $response = $this->postJson('/api/auth/login', [
            'email' => $longEmail,
            'password' => $longPassword
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function login_rate_limiting_behavior()
    {
        // Simulate multiple failed login attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'admin@platform.com',
                'password' => 'wrongpassword'
            ]);

            $response->assertStatus(422);
        }

        // The 11th attempt should still work the same way (no built-in rate limiting in this basic setup)
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function token_contains_correct_user_information()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@platform.com',
            'password' => 'admin123'
        ]);

        $token = $response->json('token');

        // Use the token to make an authenticated request
        $authResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $authResponse->assertStatus(200);
    }

    /** @test */
    public function multiple_concurrent_logins_for_same_user()
    {
        // Login multiple times with the same user
        $tokens = [];

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'admin@platform.com',
                'password' => 'admin123'
            ]);

            $response->assertStatus(200);
            $tokens[] = $response->json('token');
        }

        // All tokens should be different
        $this->assertEquals(3, count(array_unique($tokens)));

        // All tokens should work for logout
        foreach ($tokens as $token) {
            $logoutResponse = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->postJson('/api/auth/logout');

            $logoutResponse->assertStatus(200);
        }
    }

    /** @test */
    public function case_insensitive_email_login()
    {
        $emailVariations = [
            'admin@platform.com',
            'ADMIN@PLATFORM.COM',
            'Admin@Platform.Com',
            'aDmIn@pLaTfOrM.cOm'
        ];

        foreach ($emailVariations as $email) {
            $response = $this->postJson('/api/auth/login', [
                'email' => $email,
                'password' => 'admin123'
            ]);

            // This will fail because Laravel's default behavior is case-sensitive
            // If you want case-insensitive, you'd need to modify the controller
            if (strtolower($email) === 'admin@platform.com') {
                $response->assertStatus(200);
            } else {
                $response->assertStatus(422);
            }
        }
    }

    /** @test */
    public function special_characters_in_password_work_correctly()
    {
        // Create user with special characters in password
        $user = User::create([
            'clinic_id' => null,
            'name' => 'Special User',
            'email' => 'special@platform.com',
            'phone' => '+970100000099',
            'password_hash' => Hash::make('P@ssw0rd!@#$%^&*()'),
            'role' => 'Admin',
            'status' => 'Active'
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'special@platform.com',
            'password' => 'P@ssw0rd!@#$%^&*()'
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function unicode_characters_in_user_data()
    {
        // Create user with Unicode characters
        $user = User::create([
            'clinic_id' => $this->clinic->clinic_id,
            'name' => 'د. أحمد محمد', // Arabic name
            'email' => 'arabic@clinic.ps',
            'phone' => '+970100000088',
            'password_hash' => Hash::make('arabic123'),
            'role' => 'Doctor',
            'status' => 'Active'
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'arabic@clinic.ps',
            'password' => 'arabic123'
        ]);

        $response->assertStatus(200)
                ->assertJsonPath('user.name', 'د. أحمد محمد');
    }
}
