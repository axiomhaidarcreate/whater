<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Invoice;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\SalesAndInvoicingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;

class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SalesAndInvoicingService $invoicingService
    ) {}

    /**
     * Display a listing of invoices with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Invoice::with(['customer', 'warehouse', 'creator'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('invoice_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('invoice_date', '<=', $request->to_date);
        }

        $invoices = $query->paginate($request->input('per_page', 15));

        return $this->successResponse(
            data: $invoices->items(),
            message: 'تم استرجاع قائمة الفواتير بنجاح.',
            meta: [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ]
        );
    }

    /**
     * Display the specified invoice with items, payments, and registered devices.
     */
    public function show(int $id): JsonResponse
    {
        $invoice = Invoice::with([
            'customer',
            'address',
            'warehouse',
            'items.product',
            'payments.receiver',
            'customerDevices.model',
            'creator',
        ])->findOrFail($id);

        return $this->successResponse(
            data: $invoice,
            message: 'تم استرجاع تفاصيل الفاتورة بنجاح.'
        );
    }

    /**
     * Issue an invoice, deduct stock, and register water devices as pending_installation.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'customer_address_id' => ['nullable', 'exists:customer_addresses,id'],
            'sales_order_id' => ['nullable', 'exists:sales_orders,id'],
            'invoice_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.serial_number' => ['nullable', 'string', 'max:100'],
            'initial_payment' => ['nullable', 'array'],
            'initial_payment.amount' => ['required_with:initial_payment', 'numeric', 'min:0.01'],
            'initial_payment.payment_method' => ['required_with:initial_payment', 'string', 'in:cash,mada_pos,credit_card,bank_transfer,online_gateway'],
            'initial_payment.transaction_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);
        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);
        $address = isset($validated['customer_address_id'])
            ? CustomerAddress::find($validated['customer_address_id'])
            : $customer->addresses()->where('is_default', true)->first();
        $salesOrder = isset($validated['sales_order_id'])
            ? SalesOrder::find($validated['sales_order_id'])
            : null;

        $invoice = $this->invoicingService->issueInvoice(
            customer: $customer,
            warehouse: $warehouse,
            items: $validated['items'],
            invoiceDate: $validated['invoice_date'],
            address: $address,
            salesOrder: $salesOrder,
            initialPayment: $validated['initial_payment'] ?? null,
            user: $request->user()
        );

        return $this->successResponse(
            data: $invoice,
            message: 'تم إصدار الفاتورة، خصم المخزون، وربط الأجهزة المباعة بنجاح.',
            statusCode: 201
        );
    }

    /**
     * Record a payment receipt against an invoice.
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,mada_pos,credit_card,bank_transfer,online_gateway'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = Invoice::findOrFail($id);

        $payment = $this->invoicingService->recordPayment(
            invoice: $invoice,
            amount: (float) $validated['amount'],
            paymentMethod: $validated['payment_method'],
            transactionReference: $validated['transaction_reference'] ?? null,
            notes: $validated['notes'] ?? null,
            user: $request->user()
        );

        return $this->successResponse(
            data: [
                'payment' => $payment,
                'invoice' => $invoice->fresh(['payments']),
            ],
            message: 'تم تسجيل سند القبض وتحديث رصيد الفاتورة بنجاح.',
            statusCode: 201
        );
    }

    /**
     * Cancel an invoice with documented reversal (No Hard Deletes).
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $invoice = Invoice::findOrFail($id);

        $cancelledInvoice = $this->invoicingService->cancelInvoice(
            invoice: $invoice,
            reason: $validated['reason'],
            user: $request->user()
        );

        return $this->successResponse(
            data: $cancelledInvoice,
            message: 'تم إلغاء الفاتورة وإرجاع الأصناف للمخزون بحركة عكسية موثقة بنجاح.'
        );
    }

    /**
     * Render the printable Arabic HTML invoice template.
     */
    public function print(int $id): HttpResponse
    {
        $invoice = Invoice::findOrFail($id);
        $html = $this->invoicingService->renderInvoiceHtml($invoice);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
