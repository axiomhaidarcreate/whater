<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'customer_address_id',
        'warehouse_id',
        'sales_order_id',
        'maintenance_ticket_id',
        'invoice_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'grand_total',
        'paid_amount',
        'balance_due',
        'status',
        'cancellation_reason',
        'created_by',
        'reminder_date',
        'reminder_sent',
        'reminder_sent_at',
        'reminder_notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:4',
            'discount_amount' => 'decimal:4',
            'tax_amount'      => 'decimal:4',
            'grand_total'     => 'decimal:4',
            'paid_amount'     => 'decimal:4',
            'balance_due'     => 'decimal:4',
            'invoice_date'    => 'date',
            'reminder_date'   => 'date',
            'reminder_sent'   => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function customerDevices(): HasMany
    {
        return $this->hasMany(CustomerDevice::class, 'invoice_id');
    }

    /**
     * Check if invoice is fully settled.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->status === 'paid' || $this->balance_due <= 0.0001;
    }
}
