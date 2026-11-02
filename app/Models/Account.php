<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * حساب في شجرة الحسابات
 *
 * @property int    $id
 * @property string $code
 * @property string $name_ar
 * @property string $name_en
 * @property string $account_type   asset|liability|equity|revenue|expense
 * @property string $normal_balance debit|credit
 * @property int    $parent_id
 * @property int    $level
 * @property bool   $is_posting_account
 * @property bool   $is_system
 * @property bool   $is_active
 */
class Account extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'code',
        'name_ar',
        'name_en',
        'account_type',
        'normal_balance',
        'parent_id',
        'level',
        'is_posting_account',
        'is_system',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_posting_account' => 'boolean',
            'is_system'          => 'boolean',
            'is_active'          => 'boolean',
            'level'              => 'integer',
        ];
    }

    /** الحساب الأب في الشجرة */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /** الحسابات الأبناء المباشرة */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('code');
    }

    /** جميع الأبناء بشكل متكرر (Recursive) */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /** سطور القيود المرتبطة بهذا الحساب */
    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    /**
     * احتساب الرصيد الحالي للحساب
     * مدين (debit) - دائن (credit) أو العكس حسب الطبيعة الطبيعية
     */
    public function getBalance(): float
    {
        $debit  = (float) $this->journalLines()
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
            ->sum('debit');

        $credit = (float) $this->journalLines()
            ->whereHas('entry', fn ($q) => $q->where('status', 'posted'))
            ->sum('credit');

        if ($this->normal_balance === 'debit') {
            return $debit - $credit;
        }

        return $credit - $debit;
    }

    /** هل الحساب له أبناء؟ */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /** تسمية نوع الحساب بالعربي */
    public function getAccountTypeLabel(): string
    {
        return match($this->account_type) {
            'asset'     => 'أصول',
            'liability' => 'خصوم',
            'equity'    => 'حقوق الملكية',
            'revenue'   => 'إيرادات',
            'expense'   => 'مصروفات',
            default     => $this->account_type,
        };
    }
}
