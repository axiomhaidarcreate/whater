<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_can_fetch_public_settings(): void
    {
        $response = $this->getJson('/api/v1/settings/public');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status_code' => 200,
            ])
            ->assertJsonStructure([
                'data' => [
                    'company_name',
                    'company_phone',
                    'default_currency',
                    'vat_percentage',
                ],
            ]);
    }

    public function test_super_admin_can_view_all_settings(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_technician_cannot_view_all_settings(): void
    {
        $tech = User::where('phone', '0500000002')->first();
        $token = $tech->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/settings');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status_code' => 403,
                'error_code' => 'FORBIDDEN',
            ]);
    }

    public function test_admin_can_update_setting_and_audit_log_is_recorded(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/settings/reminder_window_days', [
                'value' => '21',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Check cache and database value
        $this->assertEquals(21, SystemSetting::get('reminder_window_days'));

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings.update',
            'user_id' => $admin->id,
        ]);
    }
}
