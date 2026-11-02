<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'customer_id',
        'customer_device_id',
        'customer_address_id',
        'service_type',
        'priority',
        'status',
        'technician_id',
        'supervisor_id',
        'scheduled_date',
        'time_slot',
        'arrived_at',
        'completed_at',
        'total_cost',
        'tds_input',
        'tds_output',
        'customer_signature_path',
        'customer_rating',
        'rework_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_cost' => 'decimal:4',
            'tds_input' => 'integer',
            'tds_output' => 'integer',
            'customer_rating' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'customer_address_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function spareParts(): HasMany
    {
        return $this->hasMany(TicketSparePartUsed::class, 'maintenance_ticket_id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class, 'maintenance_ticket_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'maintenance_ticket_id');
    }

    /**
     * Check if ticket is still active / open
     */
    public function isActive(): bool
    {
        return !in_array($this->status, ['approved_closed', 'cancelled']);
    }
}
