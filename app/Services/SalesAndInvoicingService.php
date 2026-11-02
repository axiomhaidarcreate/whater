<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\DeviceModel;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesAndInvoicingService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Create a new sales quotation or sales order.
     *
     * @param array<array{product_id: int, quantity: int, unit_price: float}> $items
     */
    public function createSalesOrder(
        Customer $customer,
        Warehouse $warehouse,
        array $items,
        string $orderDate,
        ?string $notes = null,
        ?User $user = null
    ): SalesOrder {
        return DB::transaction(function () use ($customer, $warehouse, $items, $orderDate, $notes, $user) {
            $vatPercentage = (float) SystemSetting::get('vat_percentage', 15);
            $subtotal = 0.0000;

            $so = SalesOrder::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'draft',
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'order_date' => $orderDate,
                'notes' => $notes,
                'created_by' => $user?->id ?? auth()->id() ?? 1,
            ]);

            foreach ($items as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineTotal = round($qty * $price, 4);
                $subtotal += $lineTotal;

                SalesOrderItem::create([
                    'sales_order_id' => $so->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                ]);
            }

            $taxAmount = round($subtotal * ($vatPercentage / 100), 4);
            $grandTotal = round($subtotal + $taxAmount, 4);

            $so->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
            ]);

            AuditLog::record(
                action: 'sales.order_created',
                model: $so,
                oldValues: null,
                newValues: ['order_number' => $so->order_number, 'grand_total' => $grandTotal],
                user: $user
            );

            return $so;
        });
    }

    /**
     * Issue an invoice, deduct stock, and automatically register water devices as 'pending_installation'.
     * Everything is wrapped in a single database transaction.
     *
     * @param array<array{product_id: int, quantity: int, unit_price: float, discount?: float, serial_number?: string}> $items
     * @param array{amount: float, payment_method: string, transaction_reference?: string}|null $initialPayment
     */
    public function issueInvoice(
        Customer $customer,
        Warehouse $warehouse,
        array $items,
        string $invoiceDate,
        ?CustomerAddress $address = null,
        ?SalesOrder $salesOrder = null,
        ?array $initialPayment = null,
        ?User $user = null
    ): Invoice {
        return DB::transaction(function () use (
            $customer,
            $warehouse,
            $items,
            $invoiceDate,
            $address,
            $salesOrder,
            $initialPayment,
            $user
        ) {
            $vatPercentage = (float) SystemSetting::get('vat_percentage', 15);
            $subtotal = 0.0000;
            $totalDiscount = 0.0000;

            // 1. Create Invoice Draft Header
            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'customer_id' => $customer->id,
                'customer_address_id' => $address?->id,
                'warehouse_id' => $warehouse->id,
                'sales_order_id' => $salesOrder?->id,
                'invoice_date' => $invoiceDate,
                'subtotal' => 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'grand_total' => 0,
                'paid_amount' => 0,
                'balance_due' => 0,
                'status' => 'draft',
                'created_by' => $user?->id ?? auth()->id() ?? 1,
            ]);

            // 2. Process Line Items and Deduct Stock
            foreach ($items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);
                $qty = (int) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $discount = isset($itemData['discount']) ? (float) $itemData['discount'] : 0.0000;
                $serialNumber = $itemData['serial_number'] ?? null;

                $lineSubtotal = round($qty * $unitPrice, 4);
                $lineTax = round(($lineSubtotal - $discount) * ($vatPercentage / 100), 4);
                $lineTotal = round(($lineSubtotal - $discount) + $lineTax, 4);

                $subtotal += $lineSubtotal;
                $totalDiscount += $discount;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discount,
                    'tax_amount' => $lineTax,
                    'total_price' => $lineTotal,
                    'serial_number' => $serialNumber,
                ]);

                // Deduct from warehouse stock (unless it's a pure service)
                if ($product->type !== 'service') {
                    $this->inventoryService->decreaseStock(
                        warehouse: $warehouse,
                        product: $product,
                        quantity: $qty,
                        movementType: 'sales_dispatch',
                        reference: $invoice,
                        notes: "صرف مبيعات بموجب الفاتورة ({$invoice->invoice_number}) للعميل ({$customer->name})",
                        user: $user
                    );
                }

                // If product is a water_device, create the CustomerDevice in 'pending_installation' state
                if ($product->type === 'water_device') {
                    $model = DeviceModel::where('model_code', 'MOD-CLASSIC-6')->first()
                        ?? DeviceModel::first();

                    for ($i = 0; $i < $qty; $i++) {
                        CustomerDevice::create([
                            'device_code' => $this->generateDeviceCode(),
                            'customer_id' => $customer->id,
                            'customer_address_id' => $address?->id,
                            'device_model_id' => $model?->id ?? 1,
                            'serial_number' => $serialNumber,
                            'invoice_id' => $invoice->id,
                            'installation_date' => null, // Pending installation
                            'warranty_expiry_date' => null,
                            'status' => 'pending_installation',
                            'notes' => "تم البيع بموجب الفاتورة رقم ({$invoice->invoice_number}). بانتظار جدولة التركيب.",
                        ]);
                    }
                }
            }

            $taxAmount = round(($subtotal - $totalDiscount) * ($vatPercentage / 100), 4);
            $grandTotal = round(($subtotal - $totalDiscount) + $taxAmount, 4);

            $invoice->update([
                'subtotal' => $subtotal,
                'discount_amount' => $totalDiscount,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'balance_due' => $grandTotal,
                'status' => 'issued',
            ]);

            // 3. Process Initial Payment if provided
            if ($initialPayment && isset($initialPayment['amount']) && $initialPayment['amount'] > 0) {
                $this->recordPayment(
                    invoice: $invoice,
                    amount: (float) $initialPayment['amount'],
                    paymentMethod: $initialPayment['payment_method'],
                    transactionReference: $initialPayment['transaction_reference'] ?? null,
                    user: $user
                );
            }

            // 4. Update sales order if linked
            if ($salesOrder) {
                $salesOrder->update(['status' => 'invoiced']);
            }

            AuditLog::record(
                action: 'invoices.issued',
                model: $invoice,
                oldValues: null,
                newValues: ['invoice_number' => $invoice->invoice_number, 'grand_total' => $grandTotal],
                user: $user
            );

            return $invoice->fresh(['items.product', 'customer', 'customerDevices', 'payments']);
        });
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(
        Invoice $invoice,
        float $amount,
        string $paymentMethod,
        ?string $transactionReference = null,
        ?string $notes = null,
        ?User $user = null
    ): Payment {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('يجب أن يكون مبلغ السداد أكبر من الصفر.');
        }

        if (round($amount, 4) > round($invoice->balance_due, 4)) {
            throw new \InvalidArgumentException(
                "المبلغ المدفوع ({$amount}) يتجاوز المتبقي من الفاتورة ({$invoice->balance_due})."
            );
        }

        return DB::transaction(function () use ($invoice, $amount, $paymentMethod, $transactionReference, $notes, $user) {
            $payment = Payment::create([
                'receipt_number' => $this->generateReceiptNumber(),
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'transaction_reference' => $transactionReference,
                'payment_date' => now()->toDateString(),
                'notes' => $notes,
                'received_by' => $user?->id ?? auth()->id() ?? 1,
            ]);

            $newPaid = round($invoice->paid_amount + $amount, 4);
            $newBalance = round($invoice->grand_total - $newPaid, 4);
            $newStatus = ($newBalance <= 0.0001) ? 'paid' : 'partially_paid';

            $invoice->update([
                'paid_amount' => $newPaid,
                'balance_due' => max(0, $newBalance),
                'status' => $newStatus,
            ]);

            AuditLog::record(
                action: 'payments.recorded',
                model: $payment,
                oldValues: ['balance_due' => $invoice->balance_due],
                newValues: ['amount' => $amount, 'new_balance' => $newBalance, 'status' => $newStatus],
                user: $user
            );

            return $payment;
        });
    }

    /**
     * Cancel an invoice with documented reversal (No Hard Deletes).
     */
    public function cancelInvoice(Invoice $invoice, string $reason, User $user): Invoice
    {
        if (in_array($invoice->status, ['cancelled', 'refunded'])) {
            throw new \InvalidArgumentException('الفاتورة ملغاة مسبقاً.');
        }

        return DB::transaction(function () use ($invoice, $reason, $user) {
            // Find related stock dispatch movements and reverse them
            $movements = StockMovement::where('reference_type', Invoice::class)
                ->where('reference_id', $invoice->id)
                ->where('type', 'sales_dispatch')
                ->get();

            foreach ($movements as $m) {
                $this->inventoryService->reverseMovement(
                    originalMovement: $m,
                    reason: "إلغاء الفاتورة رقم ({$invoice->invoice_number}): {$reason}",
                    user: $user
                );
            }

            // Decommission any linked devices that were pending installation
            CustomerDevice::where('invoice_id', $invoice->id)
                ->where('status', 'pending_installation')
                ->update([
                    'status' => 'decommissioned',
                    'notes' => "أُلغي الجهاز بسبب إلغاء الفاتورة ({$invoice->invoice_number}). السبب: {$reason}",
                ]);

            $oldStatus = $invoice->status;
            $invoice->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'balance_due' => 0.0000,
            ]);

            AuditLog::record(
                action: 'invoices.cancelled',
                model: $invoice,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => 'cancelled', 'reason' => $reason],
                user: $user
            );

            return $invoice;
        });
    }

    /**
     * Generate an Arabic print-ready HTML invoice.
     */
    public function renderInvoiceHtml(Invoice $invoice): string
    {
        $invoice->load(['items.product', 'customer', 'warehouse', 'payments']);
        $companyName = SystemSetting::get('company_name', 'شركة تقنية الفلاتر');
        $taxNumber = SystemSetting::get('tax_number', '300000000000003');
        $currency = SystemSetting::get('default_currency', 'SAR');

        return view('invoices.print', compact('invoice', 'companyName', 'taxNumber', 'currency'))->render();
    }

    protected function generateOrderNumber(): string
    {
        return 'SO-' . date('Ymd') . '-' . strtoupper(Str::random(5));
    }

    protected function generateInvoiceNumber(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(Str::random(5));
    }

    protected function generateReceiptNumber(): string
    {
        return 'RCP-' . date('Ymd') . '-' . strtoupper(Str::random(5));
    }

    protected function generateDeviceCode(): string
    {
        return 'DEV-' . date('Y') . '-' . strtoupper(Str::random(6));
    }
}
