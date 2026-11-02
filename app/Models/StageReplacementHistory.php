<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageReplacementHistory extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'stage_replacement_history';

    protected $fillable = [
        'customer_device_stage_id',
        'customer_device_id',
        'stage_number',
        'maintenance_ticket_id',
        'technician_id',
        'old_product_id',
        'new_product_id',
        'replacement_date',
        'stock_movement_item_id',
        'approved_by',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'replacement_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CustomerDeviceStage::class, 'customer_device_stage_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function oldProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'old_product_id');
    }

    public function newProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
