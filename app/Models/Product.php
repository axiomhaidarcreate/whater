<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'barcode',
        'name_ar',
        'name_en',
        'type',
        'is_compound',
        'unit',
        'cost_price',
        'selling_price',
        'reorder_level',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:4',
            'selling_price' => 'decimal:4',
            'reorder_level' => 'integer',
            'is_active' => 'boolean',
            'is_compound' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovementItems(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    /**
     * Get total quantity across all warehouses.
     */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->stockBalances()->sum('quantity');
    }
}
