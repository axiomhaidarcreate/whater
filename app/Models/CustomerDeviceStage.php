<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDeviceStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_device_id',
        'stage_number',
        'stage_name_ar',
        'product_id',
        'service_interval_days',
        'service_interval_months',
        'last_actual_replacement_at',
        'next_due_at',
        'status',
        'postponed_until',
        'exempted_reason',
        'exempted_by',
        'last_ticket_id',
        'technician_notes',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'service_interval_days' => 'integer',
            'service_interval_months' => 'integer',
            'last_actual_replacement_at' => 'date',
            'next_due_at' => 'date',
            'postponed_until' => 'date',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(CustomerDevice::class, 'customer_device_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function exemptedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exempted_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(StageReplacementHistory::class, 'customer_device_stage_id')
            ->orderBy('replacement_date', 'desc');
    }

    /**
     * Calculate days remaining until next due date.
     * Positive means days left, negative means days overdue.
     */
    public function getDaysRemainingAttribute(): int
    {
        $today = Carbon::today();
        return (int) $today->diffInDays($this->next_due_at, false);
    }

    /**
     * Calculate health percentage from 100% (freshly replaced) down to 0% (due/overdue).
     */
    public function getHealthPercentageAttribute(): int
    {
        $last = Carbon::parse($this->last_actual_replacement_at)->startOfDay();
        $due = Carbon::parse($this->next_due_at)->startOfDay();
        $today = Carbon::today();

        $totalLifespanDays = max(1, $last->diffInDays($due));

        if ($today->gte($due)) {
            return 0;
        }

        $elapsedDays = $last->diffInDays($today);
        $remainingDays = max(0, $totalLifespanDays - $elapsedDays);

        return (int) min(100, max(0, round(($remainingDays / $totalLifespanDays) * 100)));
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || ($this->next_due_at && $this->next_due_at->isPast() && !$this->next_due_at->isToday());
    }

    public function isDueToday(): bool
    {
        return $this->status === 'due_today' || ($this->next_due_at && $this->next_due_at->isToday());
    }
}
