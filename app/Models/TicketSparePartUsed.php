<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketSparePartUsed extends Model
{
    use HasFactory;

    protected $table = 'ticket_spare_parts_used';

    protected $fillable = [
        'maintenance_ticket_id',
        'product_id',
        'quantity',
        'unit_price',
        'stage_number',
        'is_warranty_covered',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:4',
            'stage_number' => 'integer',
            'is_warranty_covered' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
