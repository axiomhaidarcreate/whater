<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * رأس القيد اليومي (Journal Entry Header)
 *
 * @property int    $id
 * @property string $entry_number
 * @property string $entry_date
 * @property string $source_type
 * @property int    $source_id
 * @property string $description
 * @property float  $total_debit
 * @property float  $total_credit
 * @property string $status        draft|posted|reversed
 * @property int    $fiscal_year
 */
class JournalEntry extends Model
{
    protected $fillable = [
        'entry_number',
        'entry_date',
        'source_type',
        'source_id',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'reversed_by_entry_id',
        'fiscal_year',
        'created_by',
        'posted_by',
        'posted_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date'  => 'date',
            'posted_at'   => 'datetime',
            'total_debit' => 'decimal:4',
            'total_credit'=> 'decimal:4',
            'fiscal_year' => 'integer',
        ];
    }

    /** سطور القيد */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'journal_entry_id')
            ->orderBy('line_number');
    }

    /** المستخدم الذي أنشأ القيد */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** المستخدم الذي رحّل القيد */
    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** القيد العاكس */
    public function reversal(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversed_by_entry_id');
    }

    /** هل القيد متوازن؟ */
    public function isBalanced(): bool
    {
        return round((float) $this->total_debit - (float) $this->total_credit, 4) === 0.0;
    }

    /** هل القيد مُرحَّل؟ */
    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}
