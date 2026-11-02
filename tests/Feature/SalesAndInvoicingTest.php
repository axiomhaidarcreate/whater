<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerDevice;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesAndInvoicingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesAndInvoicingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_selling_water_device_issues_invoice_deducts_stock_and_creates_pending_device(): void
    {
        $salesRep = User::where('phone', '0500000007')->first();
        $token = $salesRep->createToken('test')->plainTextToken;

        $customer = Customer::where('primary_phone', '0509988776')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();
        $deviceProduct = Product::where('sku', 'FLT-RO-CLASSIC-6')->first();

        $initialStock = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $deviceProduct->id)
            ->value('quantity');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $deviceProduct->id,
                        'quantity' => 1,
                        'unit_price' => 850.00,
                        'serial_number' => 'DEV-SN-TEST-001',
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $invoiceId = $response->json('data.id');

        // 1. Stock deducted by 1
        $currentStock = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $deviceProduct->id)
            ->value('quantity');
        $this->assertEquals($initialStock - 1, $currentStock);

        // 2. CustomerDevice created in 'pending_installation' status
        $this->assertDatabaseHas('customer_devices', [
            'customer_id' => $customer->id,
            'invoice_id' => $invoiceId,
            'serial_number' => 'DEV-SN-TEST-001',
            'status' => 'pending_installation',
        ]);

        // 3. Stock movement ledger verified
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'sales_dispatch',
            'from_warehouse_id' => $warehouse->id,
            'reference_id' => $invoiceId,
        ]);
    }

    public function test_multiple_partial_payments_update_invoice_status_to_paid(): void
    {
        $accountant = User::where('phone', '0500000006')->first();
        $token = $accountant->createToken('test')->plainTextToken;

        $customer = Customer::where('primary_phone', '0500000004')->first();
        // The demo customer already has an invoice with total ~977.50, paid 500.00, balance ~477.50
        $invoice = Invoice::where('customer_id', $customer->id)->where('status', 'partially_paid')->first();

        $remaining = (float) $invoice->balance_due;
        $this->assertGreaterThan(0, $remaining);

        // First partial payment: 200 SAR
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/invoices/{$invoice->id}/pay", [
                'amount' => 200.00,
                'payment_method' => 'cash',
                'notes' => 'دفعة نقدية ثانية',
            ]);

        $response1->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $invoice->refresh();
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertEquals(round($remaining - 200, 2), round($invoice->balance_due, 2));

        // Final settlement payment for exact remaining balance
        $finalAmount = (float) $invoice->balance_due;
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/invoices/{$invoice->id}/pay", [
                'amount' => $finalAmount,
                'payment_method' => 'bank_transfer',
                'transaction_reference' => 'TX-FINAL-999',
            ]);

        $response2->assertStatus(201);

        $invoice->refresh();
        $this->assertEquals('paid', $invoice->status);
        $this->assertEquals(0.0000, (float) $invoice->balance_due);
    }

    public function test_overpayment_is_rejected(): void
    {
        $accountant = User::where('phone', '0500000006')->first();
        $token = $accountant->createToken('test')->plainTextToken;

        $invoice = Invoice::where('status', 'partially_paid')->first();
        $excessiveAmount = (float) $invoice->balance_due + 500.00;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/invoices/{$invoice->id}/pay", [
                'amount' => $excessiveAmount,
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(400);
    }

    public function test_sale_transaction_rolls_back_if_stock_is_insufficient(): void
    {
        $salesRep = User::where('phone', '0500000007')->first();
        $token = $salesRep->createToken('test')->plainTextToken;

        $customer = Customer::where('primary_phone', '0509988776')->first();
        $warehouse = Warehouse::where('code', 'WH-VAN-01')->first(); // Tech van has 0 devices
        $deviceProduct = Product::where('sku', 'FLT-RO-CLASSIC-6')->first();

        $initialInvoicesCount = Invoice::count();
        $initialDevicesCount = CustomerDevice::count();

        // Attempting to sell 1 device from van warehouse where stock = 0
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/invoices', [
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'invoice_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $deviceProduct->id,
                        'quantity' => 1,
                        'unit_price' => 850.00,
                    ],
                ],
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'INSUFFICIENT_STOCK',
            ]);

        // Verify transaction rolled back completely (no invoice created, no device created)
        $this->assertEquals($initialInvoicesCount, Invoice::count());
        $this->assertEquals($initialDevicesCount, CustomerDevice::count());
    }

    public function test_cancelling_invoice_creates_reverse_stock_movement_and_decommissions_device(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $invoicingService = app(SalesAndInvoicingService::class);
        $customer = Customer::where('primary_phone', '0509988776')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();
        $product = Product::where('sku', 'FLT-RO-CLASSIC-6')->first();

        // Issue fresh invoice
        $invoice = $invoicingService->issueInvoice(
            customer: $customer,
            warehouse: $warehouse,
            items: [
                ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 800.0],
            ],
            invoiceDate: now()->toDateString(),
            user: $admin
        );

        $stockBeforeCancel = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // Cancel the invoice
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/invoices/{$invoice->id}/cancel", [
                'reason' => 'إلغاء الطلب بناءً على رغبة العميل قبل التركيب',
            ]);

        $response->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals('cancelled', $invoice->status);
        $this->assertEquals('إلغاء الطلب بناءً على رغبة العميل قبل التركيب', $invoice->cancellation_reason);

        // Stock restored back by 2
        $stockAfterCancel = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');
        $this->assertEquals($stockBeforeCancel + 2, $stockAfterCancel);

        // Linked devices marked as decommissioned
        $this->assertDatabaseHas('customer_devices', [
            'invoice_id' => $invoice->id,
            'status' => 'decommissioned',
        ]);
    }

    public function test_invoice_printable_html_view_renders_successfully(): void
    {
        $salesRep = User::where('phone', '0500000007')->first();
        $token = $salesRep->createToken('test')->plainTextToken;

        $invoice = Invoice::first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get("/api/v1/invoices/{$invoice->id}/print");

        $response->assertStatus(200);
        $this->assertStringContainsString($invoice->invoice_number, $response->getContent());
        $this->assertStringContainsString('فاتورة ضريبية', $response->getContent());
    }
}
