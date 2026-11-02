<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_user_can_login_with_valid_phone_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '0500000002', // Technician Demo
            'password' => 'Tech@123456',
            'device_name' => 'Pixel 9 Pro',
            'platform' => 'android',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status_code' => 200,
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                    'user' => [
                        'id',
                        'name',
                        'phone',
                        'user_type',
                        'roles',
                        'permissions',
                    ],
                ],
            ]);

        // Verify audit log entry was created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.login',
        ]);
    }

    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '0500000002',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'status_code' => 401,
                'error_code' => 'INVALID_CREDENTIALS',
            ]);
    }

    public function test_deactivated_user_is_forbidden_from_login(): void
    {
        $user = User::where('phone', '0500000002')->first();
        $user->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '0500000002',
            'password' => 'Tech@123456',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status_code' => 403,
                'error_code' => 'ACCOUNT_DEACTIVATED',
            ]);
    }

    public function test_login_validation_fails_when_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status_code' => 422,
                'error_code' => 'VALIDATION_FAILED',
            ])
            ->assertJsonValidationErrors(['phone', 'password']);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::where('phone', '0500000002')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'phone' => '0500000002',
                ],
            ]);
    }

    public function test_authenticated_user_can_logout_and_revoke_token(): void
    {
        $user = User::where('phone', '0500000002')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح.',
            ]);

        // Verify token was revoked
        $this->assertCount(0, $user->tokens);

        // Verify audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auth.logout',
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_register_fcm_token(): void
    {
        $user = User::where('phone', '0500000002')->first();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'fcm_sample_token_xyz_123',
                'device_uuid' => 'device-uuid-98765',
                'platform' => 'android',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'device_uuid' => 'device-uuid-98765',
            'platform' => 'android',
        ]);
    }
}
