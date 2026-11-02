<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\Product;
use App\Models\StageReplacementHistory;
use App\Models\User;
use App\Services\FilterLifecycleEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilterLifecycleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_installing_device_initializes_all_bom_stages(): void
    {
        $technician = User::where('phone', '0500000002')->first();
        $token = $technician->createToken('test')->plainTextToken;

        $device = CustomerDevice::where('status', 'pending_installation')->first();
        $this->assertNotNull($device);
        $this->assertCount(0, $device->stages);

        // Confirm installation
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/customer-devices/{$device->id}/install", [
                'installation_date' => '2026-05-01',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $device->refresh();
        $this->assertEquals('active', $device->status);
        $this->assertEquals('2026-05-01', $device->installation_date->toDateString());
        $this->assertEquals('2027-05-01', $device->warranty_expiry_date->toDateString());

        // Check 6 BOM stages created with independent intervals
        $stages = $device->stages;
        $this->assertCount(6, $stages);

        // Stage 1: 90 days (3 months) -> 2026-08-01
        $stage1 = $stages->firstWhere('stage_number', 1);
        $this->assertEquals('2026-05-01', $stage1->last_actual_replacement_at->toDateString());
        $this->assertEquals('2026-08-01', $stage1->next_due_at->toDateString());

        // Stage 4: Membrane 720 days (24 months) -> 2028-05-01
        $stage4 = $stages->firstWhere('stage_number', 4);
        $this->assertEquals('2026-05-01', $stage4->last_actual_replacement_at->toDateString());
        $this->assertEquals('2028-05-01', $stage4->next_due_at->toDateString());
    }

    public function test_stage_independence_replacing_one_stage_leaves_other_stages_unchanged(): void
    {
        $technician = User::where('phone', '0500000002')->first();
        $token = $technician->createToken('test')->plainTextToken;

        $device = CustomerDevice::where('status', 'pending_installation')->first();
        $engine = app(FilterLifecycleEngine::class);
        $engine->initializeDeviceStages($device, '2026-01-01');

        $stage1 = CustomerDeviceStage::where('customer_device_id', $device->id)->where('stage_number', 1)->first();
        $stage2 = CustomerDeviceStage::where('customer_device_id', $device->id)->where('stage_number', 2)->first();

        $originalStage2Due = $stage2->next_due_at->toDateString();
        $originalStage2Last = $stage2->last_actual_replacement_at->toDateString();

        $newP1 = Product::where('sku', 'FLT-PP-10')->first();

        // Replace Stage 1 on 2026-04-01
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/stages/{$stage1->id}/replace", [
                'product_id' => $newP1->id,
                'replacement_date' => '2026-04-01',
                'notes' => 'تم استبدال الشمعة الأولى فقط بطلب العميل',
            ]);

        $response->assertStatus(200);

        // Stage 1 updated: next due is 2026-07-01
        $stage1->refresh();
        $this->assertEquals('2026-04-01', $stage1->last_actual_replacement_at->toDateString());
        $this->assertEquals('2026-07-01', $stage1->next_due_at->toDateString());

        // Stage 2 must be 100% UNCHANGED (Independence rule)
        $stage2->refresh();
        $this->assertEquals($originalStage2Last, $stage2->last_actual_replacement_at->toDateString());
        $this->assertEquals($originalStage2Due, $stage2->next_due_at->toDateString());

        // Verify history record created for Stage 1
        $this->assertDatabaseHas('stage_replacement_history', [
            'customer_device_stage_id' => $stage1->id,
            'stage_number' => 1,
        ]);
        $history = \App\Models\StageReplacementHistory::where('customer_device_stage_id', $stage1->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('2026-04-01', $history->replacement_date->toDateString());
    }

    public function test_early_replacement_resets_due_date_from_actual_replacement_date(): void
    {
        $technician = User::where('phone', '0500000002')->first();
        $token = $technician->createToken('test')->plainTextToken;

        $device = CustomerDevice::where('status', 'pending_installation')->first();
        $engine = app(FilterLifecycleEngine::class);
        $engine->initializeDeviceStages($device, '2026-01-01');

        $stage1 = CustomerDeviceStage::where('customer_device_id', $device->id)->where('stage_number', 1)->first();
        // Originally due on 2026-04-01

        // Customer requests replacement early on 2026-03-10 (21 days early)
        $newP1 = Product::where('sku', 'FLT-PP-10')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/stages/{$stage1->id}/replace", [
                'product_id' => $newP1->id,
                'replacement_date' => '2026-03-10',
                'notes' => 'تغيير مبكر استثنائي بطلب العميل',
            ]);

        $response->assertStatus(200);

        $stage1->refresh();
        $this->assertEquals('2026-03-10', $stage1->last_actual_replacement_at->toDateString());
        // New due date is 2026-03-10 + 3 months = 2026-06-10
        $this->assertEquals('2026-06-10', $stage1->next_due_at->toDateString());
    }

    public function test_postponing_stage_preserves_original_replacement_and_due_dates(): void
    {
        $supervisor = User::where('phone', '0500000008')->first();
        $token = $supervisor->createToken('test')->plainTextToken;

        $device = CustomerDevice::where('status', 'pending_installation')->first();
        $engine = app(FilterLifecycleEngine::class);
        $engine->initializeDeviceStages($device, '2026-01-01');

        $stage1 = CustomerDeviceStage::where('customer_device_id', $device->id)->where('stage_number', 1)->first();
        $originalDue = $stage1->next_due_at->toDateString();
        $originalLast = $stage1->last_actual_replacement_at->toDateString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/stages/{$stage1->id}/postpone", [
                'postponed_until' => '2026-11-15',
                'reason' => 'سفر العميل خارج المملكة لمدة شهر',
            ]);

        $response->assertStatus(200);

        $stage1->refresh();
        $this->assertEquals('postponed', $stage1->status);
        $this->assertEquals('2026-11-15', $stage1->postponed_until->toDateString());

        // Crucial: Original dates are PRESERVED and not corrupted
        $this->assertEquals($originalLast, $stage1->last_actual_replacement_at->toDateString());
        $this->assertEquals($originalDue, $stage1->next_due_at->toDateString());
    }

    public function test_visit_without_replacement_does_not_change_stage_due_dates(): void
    {
        $technician = User::where('phone', '0500000002')->first();
        $device = CustomerDevice::where('status', 'pending_installation')->first();
        $engine = app(FilterLifecycleEngine::class);
        $engine->initializeDeviceStages($device, '2026-01-01');

        $stage1 = CustomerDeviceStage::where('customer_device_id', $device->id)->where('stage_number', 1)->first();
        $dueBefore = $stage1->next_due_at->toDateString();
        $lastBefore = $stage1->last_actual_replacement_at->toDateString();

        // Technician visits to inspect pump, without changing filters
        $engine->recordVisitWithoutReplacement(
            device: $device,
            notes: 'زيارة صيانة محبس وفحص ضغط دون استبدال أي شمعة',
            technician: $technician
        );

        $stage1->refresh();
        $this->assertEquals($dueBefore, $stage1->next_due_at->toDateString());
        $this->assertEquals($lastBefore, $stage1->last_actual_replacement_at->toDateString());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'devices.visited_no_replacement',
            'auditable_id' => $device->id,
        ]);
    }
}
