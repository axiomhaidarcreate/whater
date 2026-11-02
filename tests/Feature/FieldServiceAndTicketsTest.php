<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\DeviceModel;
use App\Models\MaintenanceTicket;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\TicketAttachment;
use App\Models\TicketSparePartUsed;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FieldServiceAndTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;
    protected User $technician;
    protected Customer $customer;
    protected CustomerDevice $device;
    protected CustomerDeviceStage $stage1;
    protected Product $filterProduct;
    protected Warehouse $vanWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);
        Storage::fake('public');

        // 1. مشرف الصيانة
        $this->supervisor = User::create([
            'name' => 'مشرف الصيانة التجريبي',
            'phone' => '0500000005',
            'password' => bcrypt('password123'),
            'user_type' => 'staff',
            'is_active' => true,
        ]);
        $this->supervisor->assignRole('maintenance_supervisor');

        // 2. الفني الميداني
        $this->technician = User::create([
            'name' => 'فني الصيانة الميداني',
            'phone' => '0500000002',
            'password' => bcrypt('password123'),
            'user_type' => 'technician',
            'is_active' => true,
        ]);
        $this->technician->assignRole('technician');

        // 3. مستودع سيارة الفني مع رصيد ابتدائي
        $this->vanWarehouse = Warehouse::create([
            'code' => 'WH-VAN-01',
            'name' => 'سيارة الفني 1',
            'type' => 'technician_van',
            'assigned_user_id' => $this->technician->id,
            'is_active' => true,
        ]);

        $this->filterProduct = Product::create([
            'sku' => 'FLT-SED-01',
            'name_ar' => 'شمعة قطن بولي بروبلين',
            'type' => 'filter_cartridge',
            'cost_price' => 15.0000,
            'selling_price' => 35.0000,
            'is_active' => true,
        ]);

        StockBalance::create([
            'warehouse_id' => $this->vanWarehouse->id,
            'product_id' => $this->filterProduct->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        // 4. عميل وجهاز ومرحلة مستحقة
        $this->customer = Customer::create([
            'customer_code' => 'CUST-2026-0002',
            'name' => 'خالد بن سلطان',
            'primary_phone' => '0551122334',
            'is_active' => true,
        ]);

        $address = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'title' => 'الفيلا',
            'city' => 'الرياض',
            'district' => 'الملقا',
            'street' => 'طريق أنس بن مالك',
            'is_default' => true,
        ]);

        $model = DeviceModel::create([
            'name' => 'محطة تحلية 6 مراحل كلاسيك',
            'model_code' => 'RO-6S-CLS',
            'stages_count' => 6,
            'is_active' => true,
        ]);

        $this->device = CustomerDevice::create([
            'customer_id' => $this->customer->id,
            'device_model_id' => $model->id,
            'customer_address_id' => $address->id,
            'device_code' => 'DEV-2026-0002',
            'serial_number' => 'SN-777666',
            'status' => 'active',
            'installation_date' => '2026-01-01',
        ]);

        $this->stage1 = CustomerDeviceStage::create([
            'customer_device_id' => $this->device->id,
            'stage_number' => 1,
            'stage_name_ar' => 'المرحلة الأولى - شمعة قطن',
            'product_id' => $this->filterProduct->id,
            'service_interval_days' => 90,
            'service_interval_months' => 3,
            'last_actual_replacement_at' => '2026-01-01',
            'next_due_at' => '2026-04-01',
            'status' => 'overdue',
        ]);
    }

    public function test_supervisor_can_assign_technician_to_scheduled_ticket(): void
    {
        $supervisorToken = $this->supervisor->createToken('sup')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1001',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'status' => 'scheduled',
            'scheduled_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$supervisorToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/assign", [
                'technician_id' => $this->technician->id,
                'scheduled_date' => Carbon::tomorrow()->toDateString(),
                'time_slot' => 'morning',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ticket->refresh();
        $this->assertEquals('assigned', $ticket->status);
        $this->assertEquals($this->technician->id, $ticket->technician_id);
        $this->assertEquals($this->supervisor->id, $ticket->supervisor_id);
    }

    public function test_technician_can_check_in_at_customer_location(): void
    {
        $techToken = $this->technician->createToken('tech')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1002',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'technician_id' => $this->technician->id,
            'status' => 'assigned',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$techToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/check-in");

        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('in_progress', $ticket->status);
        $this->assertNotNull($ticket->arrived_at);
    }

    public function test_technician_can_upload_attachments_and_add_parts(): void
    {
        $techToken = $this->technician->createToken('tech')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1003',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'technician_id' => $this->technician->id,
            'status' => 'in_progress',
        ]);

        // رفع صورة ما قبل الصيانة
        $file = UploadedFile::fake()->image('before.jpg');
        $uploadResp = $this->withHeader('Authorization', "Bearer {$techToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/attachments", [
                'type' => 'before_photo',
                'file' => $file,
            ]);

        $uploadResp->assertStatus(201);
        $this->assertDatabaseHas('ticket_attachments', [
            'maintenance_ticket_id' => $ticket->id,
            'type' => 'before_photo',
            'uploaded_by' => $this->technician->id,
        ]);

        // إضافة الشمعة المستبدلة
        $partsResp = $this->withHeader('Authorization', "Bearer {$techToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/parts", [
                'product_id' => $this->filterProduct->id,
                'quantity' => 1,
                'stage_number' => 1,
            ]);

        $partsResp->assertStatus(201);
        $this->assertDatabaseHas('ticket_spare_parts_used', [
            'maintenance_ticket_id' => $ticket->id,
            'product_id' => $this->filterProduct->id,
            'stage_number' => 1,
        ]);
    }

    public function test_supervisor_approval_rejected_if_qa_gates_are_missing(): void
    {
        $supervisorToken = $this->supervisor->createToken('sup')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1004',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'technician_id' => $this->technician->id,
            'status' => 'completed_pending_review',
            'service_type' => 'routine_filter_change',
            'tds_input' => 380,
            'tds_output' => 50,
        ]);

        // لا توجد صور مرفوعة
        $response = $this->withHeader('Authorization', "Bearer {$supervisorToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/approve");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['qa_gate']);
    }

    public function test_supervisor_approves_ticket_deducts_van_inventory_and_updates_filter_stages(): void
    {
        $supervisorToken = $this->supervisor->createToken('sup')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1005',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'technician_id' => $this->technician->id,
            'status' => 'completed_pending_review',
            'service_type' => 'routine_filter_change',
            'tds_input' => 400,
            'tds_output' => 45,
            'completed_at' => '2026-05-01 10:00:00',
        ]);

        // إضافة القطعة المستبدلة
        TicketSparePartUsed::create([
            'maintenance_ticket_id' => $ticket->id,
            'product_id' => $this->filterProduct->id,
            'quantity' => 1,
            'unit_price' => 35.0000,
            'stage_number' => 1,
            'is_warranty_covered' => false,
        ]);

        // رفع جميع الصور الإلزامية للبوابات
        TicketAttachment::create([
            'maintenance_ticket_id' => $ticket->id,
            'type' => 'before_photo',
            'file_path' => 'tickets/fake_before.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $this->technician->id,
        ]);

        TicketAttachment::create([
            'maintenance_ticket_id' => $ticket->id,
            'type' => 'after_photo',
            'file_path' => 'tickets/fake_after.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $this->technician->id,
        ]);

        TicketAttachment::create([
            'maintenance_ticket_id' => $ticket->id,
            'type' => 'old_parts_photo',
            'file_path' => 'tickets/fake_parts.jpg',
            'file_size' => 1024,
            'mime_type' => 'image/jpeg',
            'uploaded_by' => $this->technician->id,
        ]);

        // اعتماد المشرف
        $response = $this->withHeader('Authorization', "Bearer {$supervisorToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $ticket->refresh();
        $this->assertEquals('approved_closed', $ticket->status);
        $this->assertEquals($this->supervisor->id, $ticket->supervisor_id);

        // 1. التحقق من خصم رصيد سيارة الفني من 10 إلى 9
        $stock = StockBalance::where('warehouse_id', $this->vanWarehouse->id)
            ->where('product_id', $this->filterProduct->id)
            ->first();
        $this->assertEquals(9, $stock->quantity);

        // 2. التحقق من تحديث تاريخ استحقاق المرحلة 1
        $this->stage1->refresh();
        $this->assertEquals('2026-05-01', $this->stage1->last_actual_replacement_at->toDateString());
        $this->assertEquals('2026-08-01', $this->stage1->next_due_at->toDateString());

        // 3. التحقق من إنشاء سجل في stage_replacement_history
        $this->assertDatabaseHas('stage_replacement_history', [
            'customer_device_stage_id' => $this->stage1->id,
            'stage_number' => 1,
            'maintenance_ticket_id' => $ticket->id,
        ]);
    }

    public function test_supervisor_can_return_ticket_for_rework_with_reason(): void
    {
        $supervisorToken = $this->supervisor->createToken('sup')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1006',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'technician_id' => $this->technician->id,
            'status' => 'completed_pending_review',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$supervisorToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/rework", [
                'rework_reason' => 'صورة العمل بعد التركيب غير واضحة وهناك تسريب بسيط يحتاج لإعادة شد التوصيلات.',
            ]);

        $response->assertStatus(200);

        $ticket->refresh();
        $this->assertEquals('returned_for_rework', $ticket->status);
        $this->assertStringContainsString('تسريب بسيط', $ticket->rework_reason);
    }

    public function test_technician_cannot_approve_their_own_ticket(): void
    {
        $techToken = $this->technician->createToken('tech')->plainTextToken;

        $ticket = MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-1007',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'technician_id' => $this->technician->id,
            'status' => 'completed_pending_review',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$techToken}")
            ->postJson("/api/v1/tickets/{$ticket->id}/approve");

        $response->assertStatus(403);
    }
}
