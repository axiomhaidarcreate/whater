<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\ProcurementService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcurementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProcurementService $procurementService
    ) {}

    /**
     * List all suppliers.
     */
    public function suppliers(): JsonResponse
    {
        $suppliers = Supplier::where('is_active', true)->get();

        return $this->successResponse(
            data: $suppliers,
            message: 'تم استرجاع قائمة الموردين بنجاح.'
        );
    }

    /**
     * Create a new supplier.
     */
    public function storeSupplier(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_code' => ['required', 'string', 'max:50', 'unique:suppliers,supplier_code'],
            'name' => ['required', 'string', 'max:191'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create($validated);

        return $this->successResponse(
            data: $supplier,
            message: 'تمت إضافة المورد بنجاح.',
            statusCode: 201
        );
    }

    /**
     * List purchase orders.
     */
    public function purchaseOrders(Request $request): JsonResponse
    {
        $query = PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'creator', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $pos = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            data: $pos->items(),
            message: 'تم استرجاع أوامر الشراء بنجاح.',
            meta: [
                'current_page' => $pos->currentPage(),
                'last_page' => $pos->lastPage(),
                'per_page' => $pos->perPage(),
                'total' => $pos->total(),
            ]
        );
    }

    /**
     * Create a new purchase order (Draft).
     */
    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $supplier = Supplier::findOrFail($validated['supplier_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        $po = $this->procurementService->createPurchaseOrder(
            supplier: $supplier,
            warehouse: $warehouse,
            items: $validated['items'],
            orderDate: $validated['order_date'],
            notes: $validated['notes'] ?? null,
            user: $request->user()
        );

        return $this->successResponse(
            data: $po->load(['items.product', 'supplier', 'warehouse']),
            message: 'تم إنشاء أمر الشراء بنجاح في حالة مسودة (لم يتأثر المخزون بعد).',
            statusCode: 201
        );
    }

    /**
     * Approve a purchase order.
     */
    public function approvePurchaseOrder(Request $request, int $id): JsonResponse
    {
        $po = PurchaseOrder::findOrFail($id);

        $approvedPo = $this->procurementService->approvePurchaseOrder(
            po: $po,
            approver: $request->user()
        );

        return $this->successResponse(
            data: $approvedPo,
            message: 'تم اعتماد أمر الشراء بنجاح، أصبح جاهزاً للاستلام المخزني.'
        );
    }

    /**
     * Receive goods against an approved purchase order (GRN).
     * This action officially increments inventory balances.
     */
    public function receiveGoods(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $po = PurchaseOrder::with(['items', 'warehouse'])->findOrFail($id);

        $grn = $this->procurementService->receiveGoods(
            po: $po,
            receivedItems: $validated['items'],
            notes: $validated['notes'] ?? null,
            receiver: $request->user()
        );

        return $this->successResponse(
            data: $grn->load(['items.product', 'warehouse', 'purchaseOrder']),
            message: 'تم استلام البضاعة، إصدار مستند الاستلام (GRN)، وزيادة رصيد المستودع فعلياً.',
            statusCode: 201
        );
    }
}
