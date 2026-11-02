<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\SalesAndInvoicingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesOrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SalesAndInvoicingService $salesService
    ) {}

    /**
     * List sales orders and quotations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SalesOrder::with(['customer', 'warehouse', 'items.product', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $orders = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            data: $orders->items(),
            message: 'تم استرجاع قائمة أوامر البيع بنجاح.',
            meta: [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        );
    }

    /**
     * Create a new sales quotation or sales order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'order_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        $so = $this->salesService->createSalesOrder(
            customer: $customer,
            warehouse: $warehouse,
            items: $validated['items'],
            orderDate: $validated['order_date'],
            notes: $validated['notes'] ?? null,
            user: $request->user()
        );

        return $this->successResponse(
            data: $so->load(['items.product', 'customer', 'warehouse']),
            message: 'تم إنشاء أمر البيع بنجاح في حالة مسودة.',
            statusCode: 201
        );
    }
}
