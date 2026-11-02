<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceModelStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_model_id',
        'stage_number',
        'stage_name_ar',
        'product_id',
        'default_lifespan_days',
        'is_mandatory',
    ];

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'default_lifespan_days' => 'integer',
            'is_mandatory' => 'boolean',
        ];
    }

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
