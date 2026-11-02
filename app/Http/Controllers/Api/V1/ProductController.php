<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of products with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'stockBalances.warehouse']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('low_stock')) {
            $query->whereHas('stockBalances', function ($q) {
                $q->havingRaw('SUM(quantity) <= products.reorder_level');
            });
        }

        $products = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            data: $products->items(),
            message: 'تم استرجاع قائمة المنتجات بنجاح.',
            meta: [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        );
    }

    /**
     * Display the specified product with stock breakdown.
     */
    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'stockBalances.warehouse'])->findOrFail($id);

        return $this->successResponse(
            data: [
                'id' => $product->id,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name_ar' => $product->name_ar,
                'name_en' => $product->name_en,
                'type' => $product->type,
                'unit' => $product->unit,
                'cost_price' => $product->cost_price,
                'selling_price' => $product->selling_price,
                'reorder_level' => $product->reorder_level,
                'is_active' => $product->is_active,
                'total_stock' => $product->total_stock,
                'warehouse_balances' => $product->stockBalances->map(function ($balance) {
                    return [
                        'warehouse_id' => $balance->warehouse_id,
                        'warehouse_name' => $balance->warehouse->name,
                        'warehouse_type' => $balance->warehouse->type,
                        'quantity' => $balance->quantity,
                        'reserved_quantity' => $balance->reserved_quantity,
                        'available_quantity' => $balance->available_quantity,
                    ];
                }),
            ],
            message: 'تم استرجاع تفاصيل المنتج بنجاح.'
        );
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:64', 'unique:products,barcode'],
            'name_ar' => ['required', 'string', 'max:191'],
            'name_en' => ['nullable', 'string', 'max:191'],
            'type' => ['required', 'string', 'in:water_device,filter_cartridge,membrane,pump,spare_part,service'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit' => ['nullable', 'string', 'max:32'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        $product = Product::create($validated);

        AuditLog::record('catalog.product_created', $product, null, $product->toArray(), $request->user());

        return $this->successResponse(
            data: $product,
            message: 'تمت إضافة المنتج بنجاح.',
            statusCode: 201
        );
    }
}
