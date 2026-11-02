<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcurementService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Create a new Purchase Order in draft status.
     * Note: Does NOT affect inventory balances.
     *
     * @param array<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createPurchaseOrder(
        Supplier $supplier,
        Warehouse $warehouse,
        array $items,
        string $orderDate,
        ?string $notes = null,
        ?User $user = null
    ): PurchaseOrder {
        return DB::transaction(function () use ($supplier, $warehouse, $items, $orderDate, $notes, $user) {
            $totalAmount = 0.0000;

            $po = PurchaseOrder::create([
                'po_number' => $this->generatePoNumber(),
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'draft',
                'total_amount' => 0,
                'order_date' => $orderDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id() ?? 1,
            ]);

            foreach ($items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineTotal = round($qty * $price, 4);
                $totalAmount += $lineTotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'ordered_qty' => $qty,
                    'received_qty' => 0,
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                ]);
            }

            $po->total_amount = $totalAmount;
            $po->save();

            AuditLog::record(
                action: 'procurement.po_created',
                model: $po,
                oldValues: null,
                newValues: ['po_number' => $po->po_number, 'total_amount' => $totalAmount],
                user: $user
            );

            return $po;
        });
    }

    /**
     * Approve a purchase order.
     */
    public function approvePurchaseOrder(PurchaseOrder $po, User $approver): PurchaseOrder
    {
        if ($po->status !== 'draft') {
            throw new \InvalidArgumentException('لا يمكن اعتماد أمر شراء ليس في حالة مسودة.');
        }

        $po->status = 'approved';
        $po->approved_by = $approver->id;
        $po->save();

        AuditLog::record(
            action: 'procurement.po_approved',
            model: $po,
            oldValues: ['status' => 'draft'],
            newValues: ['status' => 'approved', 'approved_by' => $approver->id],
            user: $approver
        );

        return $po;
    }

    /**
     * Receive goods against a purchase order (GRN).
     * THIS is the step that actually increments inventory balances.
     *
     * @param array<array{product_id: int, quantity: int, unit_cost: float}> $receivedItems
     */
    public function receiveGoods(
        PurchaseOrder $po,
        array $receivedItems,
        ?string $notes = null,
        ?User $receiver = null
    ): GoodsReceiptNote {
        if (! in_array($po->status, ['approved', 'partially_received'])) {
            throw new \InvalidArgumentException('أمر الشراء غير معتمد أو مكتمل الاستلام مسبقاً.');
        }

        return DB::transaction(function () use ($po, $receivedItems, $notes, $receiver) {
            $grn = GoodsReceiptNote::create([
                'grn_number' => $this->generateGrnNumber(),
                'purchase_order_id' => $po->id,
                'supplier_id' => $po->supplier_id,
                'warehouse_id' => $po->warehouse_id,
                'received_date' => now()->toDateString(),
                'notes' => $notes,
                'received_by' => $receiver?->id ?? auth()->id() ?? 1,
            ]);

            $warehouse = $po->warehouse;

            foreach ($receivedItems as $item) {
                $productId = $item['product_id'];
                $qty = (int) $item['quantity'];
                $cost = (float) $item['unit_cost'];

                if ($qty <= 0) {
                    continue;
                }

                $product = Product::findOrFail($productId);

                // Update PO item received quantity
                $poItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($poItem) {
                    $poItem->received_qty += $qty;
                    $poItem->save();
                }

                // Create GRN item
                GoodsReceiptNoteItem::create([
                    'goods_receipt_note_id' => $grn->id,
                    'product_id' => $productId,
                    'received_qty' => $qty,
                    'unit_cost' => $cost,
                ]);

                // Increase actual inventory balance via InventoryService
                $this->inventoryService->increaseStock(
                    warehouse: $warehouse,
                    product: $product,
                    quantity: $qty,
                    unitCost: $cost,
                    movementType: 'purchase_receipt',
                    reference: $grn,
                    notes: "استلام بضاعة بموجب مستند ({$grn->grn_number}) لأمر الشراء ({$po->po_number})",
                    user: $receiver
                );
            }

            // Check if all items in PO are fully received
            $allReceived = true;
            $anyReceived = false;
            foreach ($po->items as $checkItem) {
                if ($checkItem->received_qty < $checkItem->ordered_qty) {
                    $allReceived = false;
                }
                if ($checkItem->received_qty > 0) {
                    $anyReceived = true;
                }
            }

            $po->status = $allReceived ? 'received' : ($anyReceived ? 'partially_received' : $po->status);
            $po->save();

            AuditLog::record(
                action: 'procurement.grn_received',
                model: $grn,
                oldValues: null,
                newValues: ['grn_number' => $grn->grn_number, 'po_status' => $po->status],
                user: $receiver
            );

            return $grn;
        });
    }

    protected function generatePoNumber(): string
    {
        return 'PO-' . date('Ymd') . '-' . strtoupper(Str::random(5));
    }

    protected function generateGrnNumber(): string
    {
        return 'GRN-' . date('Ymd') . '-' . strtoupper(Str::random(5));
    }
}
