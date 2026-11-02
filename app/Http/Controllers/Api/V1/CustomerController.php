<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['addresses', 'salesRep'])->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('primary_phone', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        $customers = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            data: $customers->items(),
            message: 'تم استرجاع قائمة العملاء بنجاح.',
            meta: [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ]
        );
    }

    /**
     * Display full customer profile with devices, invoices, and addresses.
     */
    public function show(int $id): JsonResponse
    {
        $customer = Customer::with([
            'addresses',
            'devices.model',
            'invoices.items.product',
            'payments',
            'salesRep',
        ])->findOrFail($id);

        return $this->successResponse(
            data: $customer,
            message: 'تم استرجاع ملف العميل الشامل بنجاح.'
        );
    }

    /**
     * Store a newly created customer with an optional initial address.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'primary_phone' => ['required', 'string', 'max:32', 'unique:customers,primary_phone'],
            'secondary_phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:191'],
            'lead_source' => ['nullable', 'string', 'max:64'],
            'sales_rep_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'address' => ['nullable', 'array'],
            'address.title' => ['required_with:address', 'string', 'max:100'],
            'address.city' => ['required_with:address', 'string', 'max:100'],
            'address.district' => ['nullable', 'string', 'max:100'],
            'address.street' => ['nullable', 'string', 'max:191'],
            'address.latitude' => ['nullable', 'numeric'],
            'address.longitude' => ['nullable', 'numeric'],
        ]);

        $customer = DB::transaction(function () use ($validated, $request) {
            $cust = Customer::create([
                'customer_code' => $this->generateCustomerCode(),
                'name' => $validated['name'],
                'primary_phone' => $validated['primary_phone'],
                'secondary_phone' => $validated['secondary_phone'] ?? null,
                'email' => $validated['email'] ?? null,
                'lead_source' => $validated['lead_source'] ?? null,
                'sales_rep_id' => $validated['sales_rep_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => true,
            ]);

            if (! empty($validated['address'])) {
                CustomerAddress::create([
                    'customer_id' => $cust->id,
                    'title' => $validated['address']['title'],
                    'city' => $validated['address']['city'],
                    'district' => $validated['address']['district'] ?? null,
                    'street' => $validated['address']['street'] ?? null,
                    'latitude' => $validated['address']['latitude'] ?? null,
                    'longitude' => $validated['address']['longitude'] ?? null,
                    'is_default' => true,
                ]);
            }

            AuditLog::record('crm.customer_created', $cust, null, $cust->toArray(), $request->user());

            return $cust->load('addresses');
        });

        return $this->successResponse(
            data: $customer,
            message: 'تم تسجيل العميل بنجاح.',
            statusCode: 201
        );
    }

    /**
     * Add a new installation address to an existing customer.
     */
    public function addAddress(Request $request, int $id): JsonResponse
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'street' => ['nullable', 'string', 'max:191'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            $customer->addresses()->update(['is_default' => false]);
        }

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'title' => $validated['title'],
            'city' => $validated['city'],
            'district' => $validated['district'] ?? null,
            'street' => $validated['street'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
        ]);

        return $this->successResponse(
            data: $address,
            message: 'تمت إضافة العنوان الجديد للعميل بنجاح.',
            statusCode: 201
        );
    }

    protected function generateCustomerCode(): string
    {
        return 'CUST-' . strtoupper(Str::random(6));
    }
}
