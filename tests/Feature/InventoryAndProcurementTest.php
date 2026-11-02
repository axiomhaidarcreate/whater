<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAndProcurementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_creating_purchase_order_does_not_increase_inventory_stock(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();
        $supplier = Supplier::where('supplier_code', 'SUP-001')->first();

        $initialBalance = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // Create PO
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/procurement/purchase-orders', [
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'order_date' => now()->toDateString(),
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 100, 'unit_price' => 5.00],
                ],
            ]);

        $response->assertStatus(201);

        // Verify balance has NOT changed
        $currentBalance = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertEquals($initialBalance, $currentBalance);
    }

    public function test_receiving_goods_against_po_creates_grn_and_increments_stock(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();
        $supplier = Supplier::where('supplier_code', 'SUP-001')->first();

        $procurementService = app(ProcurementService::class);

        // 1. Create PO
        $po = $procurementService->createPurchaseOrder(
            supplier: $supplier,
            warehouse: $warehouse,
            items: [
                ['product_id' => $product->id, 'quantity' => 50, 'unit_price' => 5.00],
            ],
            orderDate: now()->toDateString(),
            user: $admin
        );

        // 2. Approve PO
        $procurementService->approvePurchaseOrder($po, $admin);

        $initialBalance = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // 3. Receive Goods via API
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/procurement/purchase-orders/{$po->id}/receive", [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 50, 'unit_cost' => 5.00],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Verify stock has increased by 50
        $newBalance = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertEquals($initialBalance + 50, $newBalance);

        // Verify StockMovement entry of type 'purchase_receipt'
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'purchase_receipt',
            'to_warehouse_id' => $warehouse->id,
        ]);
    }

    public function test_transfer_stock_between_warehouses_updates_balances(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();
        $fromWh = Warehouse::where('code', 'WH-MAIN')->first();
        $toWh = Warehouse::where('code', 'WH-JED')->first();

        $fromInitial = StockBalance::where('warehouse_id', $fromWh->id)->where('product_id', $product->id)->value('quantity');
        $toInitial = StockBalance::where('warehouse_id', $toWh->id)->where('product_id', $product->id)->value('quantity') ?? 0;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/inventory/transfer', [
                'from_warehouse_id' => $fromWh->id,
                'to_warehouse_id' => $toWh->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 30],
                ],
                'notes' => 'تحويل بضاعة لفرع جدة',
            ]);

        $response->assertStatus(201);

        $fromFinal = StockBalance::where('warehouse_id', $fromWh->id)->where('product_id', $product->id)->value('quantity');
        $toFinal = StockBalance::where('warehouse_id', $toWh->id)->where('product_id', $product->id)->value('quantity');

        $this->assertEquals($fromInitial - 30, $fromFinal);
        $this->assertEquals($toInitial + 30, $toFinal);
    }

    public function test_negative_stock_is_prevented_when_quantity_is_insufficient(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-RO-CLASSIC-6')->first();
        $vanWh = Warehouse::where('code', 'WH-VAN-01')->first(); // Tech van has 0 classic devices

        // Attempting to transfer 5 devices from tech van (which has 0)
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/inventory/transfer', [
                'from_warehouse_id' => $vanWh->id,
                'to_warehouse_id' => Warehouse::where('code', 'WH-MAIN')->first()->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5],
                ],
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'error_code' => 'INSUFFICIENT_STOCK',
            ]);
    }

    public function test_physical_inventory_adjustment_updates_balance_and_records_audit(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/inventory/adjust', [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'physical_count' => 450, // Set exact counted quantity
                'reason' => 'جرد نصف سنوي دوري',
            ]);

        $response->assertStatus(201);

        $newBalance = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertEquals(450, $newBalance);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'inventory.adjustment',
        ]);
    }

    public function test_reversing_stock_movement_creates_opposite_movement(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();
        $inventoryService = app(InventoryService::class);

        // Increase by 25
        $movement = $inventoryService->increaseStock(
            warehouse: $warehouse,
            product: $product,
            quantity: 25,
            unitCost: 5.0,
            movementType: 'purchase_receipt',
            user: $admin
        );

        $balanceBeforeReverse = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // Reverse the movement
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/v1/inventory/movements/{$movement->id}/reverse", [
                'reason' => 'إلغاء توريد خاطئ',
            ]);

        $response->assertStatus(201);

        $balanceAfterReverse = StockBalance::where('warehouse_id', $warehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        // Quantity should have decreased back by 25
        $this->assertEquals($balanceBeforeReverse - 25, $balanceAfterReverse);

        // Reversal movement exists
        $this->assertDatabaseHas('stock_movements', [
            'type' => 'return_out',
            'reference_id' => $movement->id,
        ]);
    }

    public function test_sales_rep_cannot_adjust_inventory_directly(): void
    {
        $sales = User::where('phone', '0500000007')->first(); // Sales Rep
        $token = $sales->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/inventory/adjust', [
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'physical_count' => 1000,
                'reason' => 'محاولة غير مصرح بها',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error_code' => 'FORBIDDEN',
            ]);
    }
}
