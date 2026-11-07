<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianTrip extends Model
{
    protected $fillable = [
        'technician_id',
        'ticket_id',
        'created_by',
        'vehicle_description',
        'destination_address',
        'latitude',
        'longitude',
        'odometer_start',
        'odometer_end',
        'km_traveled',
        'departure_time',
        'return_time',
        'work_performed',
        'notes',
        'status',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'return_time'    => 'datetime',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'dispatched' => 'تم الإرسال',
            'on_trip'    => 'في الطريق',
            'returned'   => 'عاد',
            'cancelled'  => 'ملغي',
            default      => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'dispatched' => '#f59e0b',
            'on_trip'    => '#3b82f6',
            'returned'   => '#10b981',
            'cancelled'  => '#ef4444',
            default      => '#6b7280',
        };
    }
}

