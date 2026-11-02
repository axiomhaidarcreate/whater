<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سطر من سطور القيد المزدوج
 *
 * @property int   $id
 * @property int   $journal_entry_id
 * @property int   $account_id
 * @property float $debit
 * @property float $credit
 * @property string $description
 * @property int   $line_number
 */
class JournalEntryLine extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'description',
        'customer_id',
        'supplier_id',
        'line_number',
    ];

    protected function casts(): array
    {
        return [
            'debit'       => 'decimal:4',
            'credit'      => 'decimal:4',
            'line_number' => 'integer',
        ];
    }

    /** القيد الأب */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /** الحساب المرتبط */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    /** العميل المرتبط (اختياري) */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** المورد المرتبط (اختياري) */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /** هل السطر مدين؟ */
    public function isDebit(): bool
    {
        return (float) $this->debit > 0;
    }

    /** هل السطر دائن؟ */
    public function isCredit(): bool
    {
        return (float) $this->credit > 0;
    }
}
