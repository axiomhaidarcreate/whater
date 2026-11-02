<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_code',
        'customer_id',
        'customer_address_id',
        'device_model_id',
        'serial_number',
        'invoice_id',
        'installation_date',
        'warranty_expiry_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'warranty_expiry_date' => 'date',
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

    public function model(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class, 'device_model_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function stages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CustomerDeviceStage::class)->orderBy('stage_number');
    }

    public function replacementHistory(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StageReplacementHistory::class)->orderBy('replacement_date', 'desc');
    }
}
