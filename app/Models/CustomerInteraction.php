<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInteraction extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'customer_interactions';

    protected $fillable = [
        'customer_id',
        'customer_device_id',
        'agent_id',
        'maintenance_ticket_id',
        'channel',
        'outcome',
        'follow_up_date',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'follow_up_date' => 'date',
            'created_at' => 'datetime',
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

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }
}
