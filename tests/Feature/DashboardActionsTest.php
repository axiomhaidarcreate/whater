<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeviceModel;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $accountant;
    protected Warehouse $warehouse;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->accountant = User::factory()->create([
            'phone' => '0555555555',
            'is_active' => true,
        ]);
        $this->accountant->assignRole('accountant');

        $this->warehouse = Warehouse::create([
            'code' => 'WH-MAIN',
            'name' => 'المستودع الرئيسي',
            'type' => 'central',
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'sku' => 'FLT-001',
            'name_ar' => 'شمعة قطنية مرحلة أولى',
            'type' => 'filter_cartridge',
            'cost_price' => 15.00,
            'selling_price' => 35.00,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        StockBalance::create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
        ]);
    }

    public function test_can_create_device_with_new_customer_written_directly(): void
    {
        $response = $this->actingAs($this->accountant)->post('/devices/create', [
            'customer_selection_type' => 'new',
            'new_customer_name' => 'عبد الله المنصور',
            'new_customer_phone' => '0512345678',
            'new_customer_city' => 'الرياض - حي النرجس',
            'serial_number' => 'SN-TEST-8899',
            'installation_date' => now()->toDateString(),
            'warranty_months' => 24,
            'notes' => 'تركيب تحت الحوض',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify customer created
        $customer = Customer::where('primary_phone', '0512345678')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('عبد الله المنصور', $customer->name);

        // Verify device created and linked to this customer
        $this->assertDatabaseHas('customer_devices', [
            'customer_id' => $customer->id,
            'serial_number' => 'SN-TEST-8899',
            'status' => 'active',
        ]);
    }

    public function test_accountant_can_perform_stocktaking_and_inventory_adjustment(): void
    {
        // Current balance is 20, physical count is 15 (loss of 5)
        $response = $this->actingAs($this->accountant)->post('/inventory/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'physical_count' => 15,
            'reason' => 'جرد ربع سنوي دوري وتسوية عجز',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check new balance updated to physical count (15)
        $this->assertDatabaseHas('stock_balances', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
        ]);

        // Check stock movement created with type adjustment_loss
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'adjustment_loss',
            'from_warehouse_id' => $this->warehouse->id,
        ]);
    }

    public function test_call_center_cannot_delete_customer(): void
    {
        $callCenterUser = User::factory()->create([
            'phone' => '0544444444',
            'is_active' => true,
        ]);
        $callCenterUser->assignRole('call_center');

        $customer = Customer::create([
            'customer_code' => 'CUST-TEST-001',
            'name' => 'محمد سعيد الشمري',
            'primary_phone' => '0599998877',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $response = $this->actingAs($callCenterUser)->delete("/customers/{$customer->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error', 'غير مصرح لموظف خدمة العملاء بحذف العملاء نهائياً من النظام. يرجى مراجعة الإدارة.');

        // Customer still exists in database
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'محمد سعيد الشمري',
        ]);
    }

    public function test_call_center_can_schedule_appointment_and_it_routes_directly_to_technician(): void
    {
        $callCenterUser = User::factory()->create([
            'phone' => '0544444445',
            'is_active' => true,
        ]);
        $callCenterUser->assignRole('call_center');

        $technician = User::factory()->create([
            'name' => 'فني صيانة معتمد',
            'phone' => '0533333333',
            'is_active' => true,
        ]);
        $technician->assignRole('technician');

        $customer = Customer::create([
            'customer_code' => 'CUST-TEST-002',
            'name' => 'خالد العتيبي',
            'primary_phone' => '0566667788',
            'city' => 'الرياض',
            'is_active' => true,
        ]);

        $response = $this->actingAs($callCenterUser)->post('/tickets/quick-create', [
            'customer_id' => $customer->id,
            'service_type' => 'صيانة دورية وتغيير فلاتر',
            'priority' => 'high',
            'technician_id' => $technician->id,
            'scheduled_date' => now()->addDay()->toDateString(),
            'time_slot' => 'صباحاً (09:00 - 12:00)',
            'notes' => 'موعد عاجل مجدول من خدمة العملاء',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Ticket created with assigned status and linked to technician
        $this->assertDatabaseHas('maintenance_tickets', [
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
            'service_type' => 'routine_filter_change',
            'priority' => 'high',
        ]);

        // Interaction logged in customer CRM history
        $this->assertDatabaseHas('customer_interactions', [
            'customer_id' => $customer->id,
            'outcome' => 'agreed_scheduled',
        ]);
    }

    public function test_accountant_can_create_purchase_order(): void
    {
        $supplier = \App\Models\Supplier::create([
            'supplier_code' => 'SUP-TEST-01',
            'name' => 'شركة مصافي الشرق للتجارة',
            'contact_person' => 'أحمد رضوان',
            'phone' => '0501112233',
            'email' => 'supplier@test.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->accountant)->post('/procurement/orders/create', [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'notes' => 'توريد شحنة فلاتر قطنية للمستودع',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 50,
                    'unit_price' => 12.50,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify purchase order created
        $this->assertDatabaseHas('purchase_orders', [
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'status' => 'draft',
            'total_amount' => 625.00,
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'product_id' => $this->product->id,
            'ordered_qty' => 50,
            'unit_price' => 12.50,
        ]);
    }

    public function test_call_center_dashboard_hides_purchases_suppliers_inventory_and_sales(): void
    {
        $callCenterUser = User::factory()->create([
            'phone' => '0544444446',
            'is_active' => true,
        ]);
        $callCenterUser->assignRole('call_center');

        $response = $this->actingAs($callCenterUser)->get('/dashboard');

        $response->assertStatus(200);

        // Assert that forbidden ERP tabs and modals are NOT rendered for call center
        $response->assertDontSee('id="tab-sales"', false);
        $response->assertDontSee('id="tab-suppliers"', false);
        $response->assertDontSee('id="tab-inventory"', false);
        $response->assertDontSee('id="newSaleModal"', false);
        $response->assertDontSee('id="newSupplierModal"', false);
        $response->assertDontSee('id="newPurchaseOrderModal"', false);
        $response->assertDontSee('id="stockAuditModal"', false);
        $response->assertDontSee('إدارة الـ ERP والمالية');
        $response->assertDontSee('أحدث المعاملات المالية والمبيعات');

        // Assert that call center allowed modules ARE rendered
        $response->assertSee('id="tab-customers"', false);
        $response->assertSee('id="tab-tickets"', false);
        $response->assertSee('id="tab-lifecycle"', false);
        $response->assertSee('id="tab-appointments"', false);
        $response->assertSee('المواعيد');
        $response->assertSee('سجل تفاعلات خدمة العملاء');
    }

    public function test_call_center_cannot_perform_erp_actions(): void
    {
        $callCenterUser = User::factory()->create([
            'phone' => '0544444447',
            'is_active' => true,
        ]);
        $callCenterUser->assignRole('call_center');

        // 1. Cannot create sale
        $saleResponse = $this->actingAs($callCenterUser)->post('/sales/create', [
            'customer_type' => 'existing',
            'customer_id' => 1,
            'warehouse_id' => $this->warehouse->id,
            'sale_type' => 'invoice',
            'order_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);
        $saleResponse->assertRedirect();
        $saleResponse->assertSessionHas('error', 'غير مصرح لموظف خدمة العملاء بإنشاء أوامر بيع أو فواتير.');

        // 2. Cannot create supplier
        $supplierResponse = $this->actingAs($callCenterUser)->post('/suppliers/create', [
            'name' => 'مورد غير مسموح',
            'phone' => '0509999999',
        ]);
        $supplierResponse->assertRedirect();
        $supplierResponse->assertSessionHas('error', 'غير مصرح لموظف خدمة العملاء بإضافة موردين.');

        // 3. Cannot create purchase order
        $poResponse = $this->actingAs($callCenterUser)->post('/procurement/orders/create', [
            'supplier_id' => 1,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 10, 'unit_price' => 10],
            ],
        ]);
        $poResponse->assertRedirect();
        $poResponse->assertSessionHas('error', 'غير مصرح لموظف خدمة العملاء بإنشاء أوامر شراء ومشتريات.');

        // 4. Cannot adjust inventory
        $invResponse = $this->actingAs($callCenterUser)->post('/inventory/adjust', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $this->product->id,
            'physical_count' => 100,
            'reason' => 'محاولة غير مصرحة',
        ]);
        $invResponse->assertRedirect();
        $invResponse->assertSessionHas('error', 'غير مصرح لموظف خدمة العملاء بإجراء جرد أو تسوية للمخزون.');
    }
}

