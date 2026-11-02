<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\DeviceModel;
use App\Models\MaintenanceTicket;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallCenterAndTelesalesTest extends TestCase
{
    use RefreshDatabase;

    protected User $callCenterAgent;
    protected User $technician;
    protected Customer $customer;
    protected CustomerDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SystemSettingsSeeder::class);

        // إنشاء موظف خدمة عملاء
        $this->callCenterAgent = User::create([
            'name' => 'موظف الكول سنتر',
            'phone' => '0500000004',
            'password' => bcrypt('password123'),
            'user_type' => 'staff',
            'is_active' => true,
        ]);
        $this->callCenterAgent->assignRole('call_center');

        // إنشاء فني للتأكد من حظر الصلاحيات
        $this->technician = User::create([
            'name' => 'الفني التجريبي',
            'phone' => '0500000002',
            'password' => bcrypt('password123'),
            'user_type' => 'technician',
            'is_active' => true,
        ]);
        $this->technician->assignRole('technician');

        // إنشاء عميل وعنوان
        $this->customer = Customer::create([
            'customer_code' => 'CUST-2026-0001',
            'name' => 'سعد ناصر العتيبي',
            'primary_phone' => '0559876543',
            'is_active' => true,
        ]);

        $address = CustomerAddress::create([
            'customer_id' => $this->customer->id,
            'title' => 'المنزل',
            'city' => 'الرياض',
            'district' => 'الياسمين',
            'street' => 'شارع القادسية',
            'is_default' => true,
        ]);

        // موديل ومنتجات
        $model = DeviceModel::create([
            'name' => 'محطة تحلية 6 مراحل كلاسيك',
            'model_code' => 'RO-6S-CLS',
            'stages_count' => 3,
            'is_active' => true,
        ]);

        $p1 = Product::create([
            'name_ar' => 'شمعة قطن بولي بروبلين',
            'name_en' => 'PP Sediment',
            'sku' => 'FLT-SED-01',
            'type' => 'filter_cartridge',
            'selling_price' => 35.0000,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'name_ar' => 'شمعة كربون حبيبي GAC',
            'name_en' => 'GAC Carbon',
            'sku' => 'FLT-GAC-02',
            'type' => 'filter_cartridge',
            'selling_price' => 45.0000,
            'is_active' => true,
        ]);

        $this->device = CustomerDevice::create([
            'customer_id' => $this->customer->id,
            'device_model_id' => $model->id,
            'customer_address_id' => $address->id,
            'device_code' => 'DEV-2026-0001',
            'serial_number' => 'SN-999888',
            'status' => 'active',
            'installation_date' => '2026-01-01',
        ]);

        // مرحلة 1: متأخرة overdue
        CustomerDeviceStage::create([
            'customer_device_id' => $this->device->id,
            'stage_number' => 1,
            'stage_name_ar' => 'المرحلة الأولى - شمعة قطن',
            'product_id' => $p1->id,
            'service_interval_days' => 90,
            'service_interval_months' => 3,
            'last_actual_replacement_at' => '2026-01-01',
            'next_due_at' => '2026-04-01',
            'status' => 'overdue',
        ]);

        // مرحلة 2: قادمة upcoming
        CustomerDeviceStage::create([
            'customer_device_id' => $this->device->id,
            'stage_number' => 2,
            'stage_name_ar' => 'المرحلة الثانية - كربون حبيبي',
            'product_id' => $p2->id,
            'service_interval_days' => 180,
            'service_interval_months' => 6,
            'last_actual_replacement_at' => '2026-01-01',
            'next_due_at' => Carbon::today()->addDays(5)->toDateString(),
            'status' => 'upcoming',
        ]);
    }

    public function test_call_center_agent_can_fetch_due_filters_with_overdue_sorting(): void
    {
        $token = $this->callCenterAgent->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/call-center/due-filters');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals(1, $data[0]['stage_number']);
        $this->assertEquals('overdue', $data[0]['stage_status']);
        $this->assertGreaterThan(0, $data[0]['days_overdue']);
        $this->assertFalse($data[0]['has_open_ticket']);
        $this->assertEquals('سعد ناصر العتيبي', $data[0]['customer_name']);
    }

    public function test_filtering_due_filters_by_city_and_status(): void
    {
        $token = $this->callCenterAgent->createToken('test')->plainTextToken;

        // تصفية بمدينة الرياض وحالة overdue
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/call-center/due-filters?city=الرياض&filter_status=overdue');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('overdue', $data[0]['stage_status']);

        // تصفية بمدينة جدة (لا توجد بيانات)
        $responseJeddah = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/call-center/due-filters?city=جدة');

        $responseJeddah->assertStatus(200);
        $this->assertEmpty($responseJeddah->json('data'));
    }

    public function test_recording_agreed_interaction_creates_ticket_and_parts_used(): void
    {
        $token = $this->callCenterAgent->createToken('test')->plainTextToken;

        $payload = [
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'channel' => 'phone_call',
            'outcome' => 'agreed_scheduled',
            'notes' => 'وافق العميل على زيارة الصيانة غداً صباحاً',
            'scheduled_date' => Carbon::tomorrow()->toDateString(),
            'time_slot' => 'morning',
            'create_ticket' => true,
            'requested_stages' => [1, 2],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/call-center/interactions', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $this->customer->id,
            'outcome' => 'agreed_scheduled',
            'channel' => 'phone_call',
        ]);

        $this->assertDatabaseHas('maintenance_tickets', [
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'status' => 'scheduled',
            'time_slot' => 'morning',
        ]);

        $ticket = MaintenanceTicket::where('customer_device_id', $this->device->id)->first();
        $this->assertNotNull($ticket);
        $this->assertStringStartsWith('TCK-', $ticket->ticket_number);

        // قطع الغيار المطلوبة مرتبطة بالتذكرة (المرحلتين 1 و 2)
        $this->assertEquals(2, $ticket->spareParts()->count());
    }

    public function test_strict_duplicate_ticket_prevention_rejects_second_active_ticket(): void
    {
        $token = $this->callCenterAgent->createToken('test')->plainTextToken;

        // أولاً: إنشاء أمر صيانة نشط
        MaintenanceTicket::create([
            'ticket_number' => 'TCK-2026-0001',
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'status' => 'scheduled',
            'scheduled_date' => Carbon::tomorrow()->toDateString(),
        ]);

        // ثانياً: محاولة إنشاء أمر صيانة جديد لنفس الجهاز
        $payload = [
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'channel' => 'phone_call',
            'outcome' => 'agreed_scheduled',
            'create_ticket' => true,
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/call-center/interactions', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_device_id']);

        // التأكد من عدم إنشاء تذكرة ثانية
        $this->assertEquals(1, MaintenanceTicket::where('customer_device_id', $this->device->id)->count());
    }

    public function test_recording_postponed_interaction_updates_stage_status_and_due_date(): void
    {
        $token = $this->callCenterAgent->createToken('test')->plainTextToken;

        $postponedDate = Carbon::today()->addDays(20)->toDateString();

        $payload = [
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'channel' => 'phone_call',
            'outcome' => 'postponed',
            'postponed_until' => $postponedDate,
            'notes' => 'العميل مسافر وسيتم التواصل بعد 20 يوماً',
            'requested_stages' => [1],
        ];

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/call-center/interactions', $payload);

        $response->assertStatus(201);

        // التأكد من تحديث حالة المرحلة في جدول customer_device_stages
        $stage1 = CustomerDeviceStage::where('customer_device_id', $this->device->id)
            ->where('stage_number', 1)
            ->first();

        $this->assertEquals('postponed', $stage1->status);
        $this->assertEquals($postponedDate, $stage1->postponed_until->toDateString());
    }

    public function test_unauthorized_user_cannot_access_telesales_endpoints(): void
    {
        $token = $this->technician->createToken('tech-test')->plainTextToken;

        // الفني لا يملك telesales.view
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/call-center/due-filters');

        $response->assertStatus(403);
    }

    public function test_call_center_can_schedule_appointment_and_assign_technician_directly(): void
    {
        $callCenterToken = $this->callCenterAgent->createToken('cc-test')->plainTextToken;
        $scheduledDate = Carbon::tomorrow()->toDateString();

        $payload = [
            'customer_id' => $this->customer->id,
            'customer_device_id' => $this->device->id,
            'channel' => 'phone_call',
            'outcome' => 'agreed_scheduled',
            'create_ticket' => true,
            'scheduled_date' => $scheduledDate,
            'time_slot' => 'evening',
            'technician_id' => $this->technician->id,
            'service_type' => 'routine_filter_change',
            'requested_stages' => [1, 2],
            'notes' => 'تم الاتفاق مع العميل وتحديد الفني الميداني',
        ];

        $response = $this->withHeader('Authorization', "Bearer {$callCenterToken}")
            ->postJson('/api/v1/call-center/interactions', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'assigned',
                    'technician_id' => $this->technician->id,
                ],
            ]);

        // التحقق من ظهور الموعد للفني الميداني بكامل بياناته
        $techToken = $this->technician->createToken('tech-view')->plainTextToken;
        $techResponse = $this->withHeader('Authorization', "Bearer {$techToken}")
            ->getJson('/api/v1/tickets');

        $techResponse->assertStatus(200)
            ->assertJsonFragment([
                'technician_id' => $this->technician->id,
                'status' => 'assigned',
            ]);
    }
}
