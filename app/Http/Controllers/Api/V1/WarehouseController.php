<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StockBalance;
use App\Models\Warehouse;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    use ApiResponse;

    /**
     * List all warehouses with assigned technician/user.
     */
    public function index(): JsonResponse
    {
        $warehouses = Warehouse::with('assignedUser')->get();

        return $this->successResponse(
            data: $warehouses,
            message: 'تم استرجاع قائمة المستودعات وسيارات الفنيين بنجاح.'
        );
    }

    /**
     * Get stock balances for a specific warehouse.
     */
    public function stock(int $id): JsonResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        $balances = StockBalance::with('product')
            ->where('warehouse_id', $warehouse->id)
            ->get()
            ->map(function ($b) {
                return [
                    'product_id' => $b->product_id,
                    'sku' => $b->product->sku,
                    'name_ar' => $b->product->name_ar,
                    'type' => $b->product->type,
                    'quantity' => $b->quantity,
                    'reserved_quantity' => $b->reserved_quantity,
                    'available_quantity' => $b->available_quantity,
                    'reorder_level' => $b->product->reorder_level,
                    'is_low_stock' => $b->quantity <= $b->product->reorder_level,
                ];
            });

        return $this->successResponse(
            data: [
                'warehouse' => [
                    'id' => $warehouse->id,
                    'code' => $warehouse->code,
                    'name' => $warehouse->name,
                    'type' => $warehouse->type,
                ],
                'stock' => $balances,
            ],
            message: 'تم استرجاع رصيد المستودع بنجاح.'
        );
    }
}
