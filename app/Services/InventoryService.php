<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockMovementItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventoryService
{
    /**
     * Increase stock balance for a product in a warehouse with a documented movement.
     */
    public function increaseStock(
        Warehouse $warehouse,
        Product $product,
        int $quantity,
        float $unitCost,
        string $movementType,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('يجب أن تكون الكمية المضافة أكبر من الصفر.');
        }

        return DB::transaction(function () use ($warehouse, $product, $quantity, $unitCost, $movementType, $reference, $notes, $user) {
            // Lock or create stock balance row
            $balance = StockBalance::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = StockBalance::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $oldQuantity = $balance->quantity;
            $balance->quantity += $quantity;
            $balance->save();

            // Create movement header
            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'type' => $movementType,
                'from_warehouse_id' => null,
                'to_warehouse_id' => $warehouse->id,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            // Create movement line item
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
            ]);

            // Audit log
            AuditLog::record(
                action: 'inventory.increase',
                model: $balance,
                oldValues: ['quantity' => $oldQuantity],
                newValues: ['quantity' => $balance->quantity, 'added' => $quantity, 'movement_number' => $movement->movement_number],
                user: $user
            );

            return $movement;
        });
    }

    /**
     * Decrease stock balance with pessimistic locking and negative-stock prevention.
     */
    public function decreaseStock(
        Warehouse $warehouse,
        Product $product,
        int $quantity,
        string $movementType,
        ?Model $reference = null,
        ?string $notes = null,
        ?User $user = null
    ): StockMovement {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('يجب أن تكون الكمية المصروفة أكبر من الصفر.');
        }

        return DB::transaction(function () use ($warehouse, $product, $quantity, $movementType, $reference, $notes, $user) {
            // Lock stock balance row for update
            $balance = StockBalance::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            if (! $balance || $balance->quantity < $quantity) {
                $available = $balance ? $balance->quantity : 0;
                throw new InsufficientStockException(
                    "الرصيد غير كافٍ في مستودع ({$warehouse->name}) للصنف ({$product->name_ar}). المطلوب: {$quantity}، المتاح: {$available}."
                );
            }

            $oldQuantity = $balance->quantity;
            $balance->quantity -= $quantity;
            $balance->save();

            // Create movement header
            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'type' => $movementType,
                'from_warehouse_id' => $warehouse->id,
                'to_warehouse_id' => null,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->getKey(),
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            // Create movement line item
            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $product->cost_price,
            ]);

            // Audit log
            AuditLog::record(
                action: 'inventory.decrease',
                model: $balance,
                oldValues: ['quantity' => $oldQuantity],
                newValues: ['quantity' => $balance->quantity, 'deducted' => $quantity, 'movement_number' => $movement->movement_number],
                user: $user
            );

            return $movement;
        });
    }

    /**
     * Transfer stock between warehouses or to technician van.
     *
     * @param array<array{product_id: int, quantity: int}> $items
     */
    public function transferStock(
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse,
        array $items,
        ?string $notes = null,
        ?User $user = null
    ): StockMovement {
        if ($fromWarehouse->id === $toWarehouse->id) {
            throw new \InvalidArgumentException('المستودع المصدر والمستودع الوجهة متطابقان.');
        }

        return DB::transaction(function () use ($fromWarehouse, $toWarehouse, $items, $notes, $user) {
            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'type' => 'transfer',
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            foreach ($items as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                $product = Product::findOrFail($productId);

                // Lock sender balance
                $fromBalance = StockBalance::where('warehouse_id', $fromWarehouse->id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $fromBalance || $fromBalance->quantity < $quantity) {
                    $available = $fromBalance ? $fromBalance->quantity : 0;
                    throw new InsufficientStockException(
                        "الرصيد غير كافٍ في مستودع التحويل ({$fromWarehouse->name}) للصنف ({$product->name_ar}). المتاح: {$available}، المطلوب: {$quantity}."
                    );
                }

                $fromBalance->quantity -= $quantity;
                $fromBalance->save();

                // Lock receiver balance
                $toBalance = StockBalance::where('warehouse_id', $toWarehouse->id)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (! $toBalance) {
                    $toBalance = StockBalance::create([
                        'warehouse_id' => $toWarehouse->id,
                        'product_id' => $productId,
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                    ]);
                }

                $toBalance->quantity += $quantity;
                $toBalance->save();

                // Line item
                StockMovementItem::create([
                    'stock_movement_id' => $movement->id,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_cost' => $product->cost_price,
                ]);
            }

            return $movement;
        });
    }

    /**
     * Physical inventory count adjustment (تسوية جردية - عجز أو فائض).
     */
    public function adjustStock(
        Warehouse $warehouse,
        Product $product,
        int $physicalCount,
        string $reason,
        ?User $user = null
    ): StockMovement {
        if ($physicalCount < 0) {
            throw new \InvalidArgumentException('الرصيد الفعلي لا يمكن أن يكون سالباً.');
        }

        return DB::transaction(function () use ($warehouse, $product, $physicalCount, $reason, $user) {
            $balance = StockBalance::where('warehouse_id', $warehouse->id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first();

            $currentQty = $balance ? $balance->quantity : 0;
            $diff = $physicalCount - $currentQty;

            if ($diff === 0) {
                throw new \InvalidArgumentException('الرصيد الفعلي مطابق للرصيد الدفتري الحالي.');
            }

            $type = $diff > 0 ? 'adjustment_gain' : 'adjustment_loss';
            $qtyChange = abs($diff);

            if (! $balance) {
                $balance = StockBalance::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $product->id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                ]);
            }

            $balance->quantity = $physicalCount;
            $balance->save();

            $movement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'type' => $type,
                'from_warehouse_id' => $diff < 0 ? $warehouse->id : null,
                'to_warehouse_id' => $diff > 0 ? $warehouse->id : null,
                'notes' => "تسوية جردية: {$reason}",
                'created_by' => $user?->id ?? auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            StockMovementItem::create([
                'stock_movement_id' => $movement->id,
                'product_id' => $product->id,
                'quantity' => $qtyChange,
                'unit_cost' => $product->cost_price,
            ]);

            AuditLog::record(
                action: 'inventory.adjustment',
                model: $balance,
                oldValues: ['quantity' => $currentQty],
                newValues: ['quantity' => $physicalCount, 'diff' => $diff, 'reason' => $reason],
                user: $user
            );

            return $movement;
        });
    }

    /**
     * Create a reversal movement (حركة عكسية موثقة لإلغاء حركة سابقة).
     */
    public function reverseMovement(
        StockMovement $originalMovement,
        string $reason,
        ?User $user = null
    ): StockMovement {
        return DB::transaction(function () use ($originalMovement, $reason, $user) {
            $reverseType = match ($originalMovement->type) {
                'purchase_receipt' => 'return_out',
                'sales_dispatch' => 'return_in',
                'ticket_consumption' => 'return_in',
                'adjustment_gain' => 'adjustment_loss',
                'adjustment_loss' => 'adjustment_gain',
                default => 'return_in',
            };

            $reversedMovement = StockMovement::create([
                'movement_number' => $this->generateMovementNumber(),
                'type' => $reverseType,
                'from_warehouse_id' => $originalMovement->to_warehouse_id,
                'to_warehouse_id' => $originalMovement->from_warehouse_id,
                'reference_type' => StockMovement::class,
                'reference_id' => $originalMovement->id,
                'notes' => "إلغاء وعكس الحركة ({$originalMovement->movement_number}): {$reason}",
                'created_by' => $user?->id ?? auth()->id() ?? 1,
                'created_at' => now(),
            ]);

            foreach ($originalMovement->items as $item) {
                // If original movement increased warehouse stock (to_warehouse), the reversal decreases it
                if ($originalMovement->to_warehouse_id) {
                    $balance = StockBalance::where('warehouse_id', $originalMovement->to_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $balance || $balance->quantity < $item->quantity) {
                        throw new InsufficientStockException("لا يمكن إلغاء الحركة، الرصيد المتبقي أقل من الكمية المعكوسة.");
                    }

                    $balance->quantity -= $item->quantity;
                    $balance->save();
                }

                // If original movement decreased warehouse stock (from_warehouse), the reversal restores it
                if ($originalMovement->from_warehouse_id) {
                    $balance = StockBalance::where('warehouse_id', $originalMovement->from_warehouse_id)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (! $balance) {
                        $balance = StockBalance::create([
                            'warehouse_id' => $originalMovement->from_warehouse_id,
                            'product_id' => $item->product_id,
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                        ]);
                    }

                    $balance->quantity += $item->quantity;
                    $balance->save();
                }

                StockMovementItem::create([
                    'stock_movement_id' => $reversedMovement->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                ]);
            }

            return $reversedMovement;
        });
    }

    /**
     * Generate unique sequential movement number (e.g. SM-20261029-0001).
     */
    protected function generateMovementNumber(): string
    {
        $datePrefix = date('Ymd');
        $random = strtoupper(Str::random(5));

        return "SM-{$datePrefix}-{$random}";
    }
}
