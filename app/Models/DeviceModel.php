<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_code',
        'name',
        'stages_count',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'stages_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The Bill of Materials (BOM) stages defining default filters and lifespans.
     */
    public function stages(): HasMany
    {
        return $this->hasMany(DeviceModelStage::class)->orderBy('stage_number');
    }
}
