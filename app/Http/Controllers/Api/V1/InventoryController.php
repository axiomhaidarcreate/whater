<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display stock movement ledger with full auditing and filters.
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['fromWarehouse', 'toWarehouse', 'creator', 'items.product'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('warehouse_id')) {
            $whId = $request->warehouse_id;
            $query->where(function ($q) use ($whId) {
                $q->where('from_warehouse_id', $whId)
                    ->orWhere('to_warehouse_id', $whId);
            });
        }

        if ($request->filled('product_id')) {
            $prodId = $request->product_id;
            $query->whereHas('items', function ($q) use ($prodId) {
                $q->where('product_id', $prodId);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $movements = $query->paginate($request->input('per_page', 20));

        return $this->successResponse(
            data: $movements->items(),
            message: 'تم استرجاع دفتر حركات المخزون بنجاح.',
            meta: [
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'per_page' => $movements->perPage(),
                'total' => $movements->total(),
            ]
        );
    }

    /**
     * Transfer stock between warehouses or to a technician van.
     */
    public function transfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'exists:warehouses,id', 'different:from_warehouse_id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $from = Warehouse::findOrFail($validated['from_warehouse_id']);
        $to = Warehouse::findOrFail($validated['to_warehouse_id']);

        $movement = $this->inventoryService->transferStock(
            fromWarehouse: $from,
            toWarehouse: $to,
            items: $validated['items'],
            notes: $validated['notes'] ?? null,
            user: $request->user()
        );

        return $this->successResponse(
            data: $movement->load(['items.product', 'fromWarehouse', 'toWarehouse']),
            message: 'تم تسجيل تحويل المخزون بنجاح.',
            statusCode: 201
        );
    }

    /**
     * Physical inventory count adjustment (تسوية جردية).
     */
    public function adjust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'product_id' => ['required', 'exists:products,id'],
            'physical_count' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $product = Product::findOrFail($validated['product_id']);

        $movement = $this->inventoryService->adjustStock(
            warehouse: $warehouse,
            product: $product,
            physicalCount: $validated['physical_count'],
            reason: $validated['reason'],
            user: $request->user()
        );

        return $this->successResponse(
            data: $movement->load(['items.product']),
            message: 'تم تسجيل التسوية الجردية وتعديل الرصيد الدفتري بنجاح.',
            statusCode: 201
        );
    }

    /**
     * Reverse an existing movement.
     */
    public function reverse(Request $request, int $movementId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $original = StockMovement::with('items')->findOrFail($movementId);

        $reversed = $this->inventoryService->reverseMovement(
            originalMovement: $original,
            reason: $validated['reason'],
            user: $request->user()
        );

        return $this->successResponse(
            data: $reversed->load(['items.product']),
            message: 'تم إنشاء الحركة العكسية وإلغاء أثر الحركة السابقة بنجاح.',
            statusCode: 201
        );
    }
}
