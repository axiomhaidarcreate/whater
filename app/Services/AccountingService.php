<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * خدمة المحاسبة المزدوجة
 * =================================================================
 * تُنفّذ قواعد القيد المزدوج Double-Entry Bookkeeping بصرامة:
 *  - مجموع المدين = مجموع الدائن في كل قيد
 *  - لا يُرحَّل قيد غير متوازن
 *  - القيود المُرحَّلة لا تُحذف — يتم عكسها فقط
 *  - الحسابات النظامية (is_system) لا تُحذف
 * =================================================================
 */
class AccountingService
{
    // ─────────────────────────────────────────────────────────────
    // شجرة الحسابات
    // ─────────────────────────────────────────────────────────────

    /**
     * جلب شجرة الحسابات الكاملة (هرمي مع الأبناء)
     */
    public function getChartOfAccounts(array $filters = []): Collection
    {
        $query = Account::with('children.children')
            ->whereNull('parent_id') // الجذور فقط
            ->orderBy('code');

        if (!empty($filters['account_type'])) {
            $query->where('account_type', $filters['account_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->get();
    }

    /**
     * جلب قائمة مسطحة للحسابات القابلة للترحيل (للـ dropdowns)
     */
    public function getPostingAccounts(?string $accountType = null): Collection
    {
        $query = Account::where('is_posting_account', true)
            ->where('is_active', true)
            ->orderBy('code');

        if ($accountType) {
            $query->where('account_type', $accountType);
        }

        return $query->get(['id', 'code', 'name_ar', 'account_type', 'normal_balance']);
    }

    /**
     * إنشاء حساب جديد في شجرة الحسابات
     */
    public function createAccount(array $data): Account
    {
        // التحقق من عدم تكرار الكود
        if (Account::where('code', $data['code'])->exists()) {
            throw ValidationException::withMessages([
                'code' => 'رمز الحساب مستخدم بالفعل.',
            ]);
        }

        // احتساب المستوى من الأب
        $level = 1;
        if (!empty($data['parent_id'])) {
            $parent = Account::findOrFail($data['parent_id']);

            // التحقق من أن الأب حساب تجميعي (ليس ختامياً)
            if ($parent->is_posting_account && $parent->children()->exists()) {
                throw ValidationException::withMessages([
                    'parent_id' => 'لا يمكن إضافة حساب فرعي تحت حساب ختامي يحتوي قيوداً.',
                ]);
            }

            $level = $parent->level + 1;

            // إذا أصبح الأب يحتوي أبناء، يصبح تجميعياً تلقائياً
            $parent->update(['is_posting_account' => false]);
        }

        return Account::create([
            'code'               => $data['code'],
            'name_ar'            => $data['name_ar'],
            'name_en'            => $data['name_en'] ?? null,
            'account_type'       => $data['account_type'],
            'normal_balance'     => $data['normal_balance'],
            'parent_id'          => $data['parent_id'] ?? null,
            'level'              => $level,
            'is_posting_account' => $data['is_posting_account'] ?? true,
            'is_system'          => false,
            'is_active'          => $data['is_active'] ?? true,
            'notes'              => $data['notes'] ?? null,
        ]);
    }

    /**
     * تعديل حساب موجود (الحسابات النظامية محمية جزئياً)
     */
    public function updateAccount(Account $account, array $data): Account
    {
        if ($account->is_system) {
            // الحسابات النظامية: يُسمح بتعديل الاسم والملاحظات فقط
            $account->update([
                'name_ar' => $data['name_ar'] ?? $account->name_ar,
                'name_en' => $data['name_en'] ?? $account->name_en,
                'notes'   => $data['notes'] ?? $account->notes,
                'is_active' => $data['is_active'] ?? $account->is_active,
            ]);
        } else {
            // التحقق من تكرار الكود إن تغيّر
            if (isset($data['code']) && $data['code'] !== $account->code) {
                if (Account::where('code', $data['code'])->where('id', '!=', $account->id)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => 'رمز الحساب مستخدم بالفعل.',
                    ]);
                }
            }

            $account->update(array_filter([
                'code'               => $data['code'] ?? $account->code,
                'name_ar'            => $data['name_ar'] ?? $account->name_ar,
                'name_en'            => $data['name_en'] ?? $account->name_en,
                'is_posting_account' => $data['is_posting_account'] ?? $account->is_posting_account,
                'is_active'          => $data['is_active'] ?? $account->is_active,
                'notes'              => $data['notes'] ?? $account->notes,
            ], fn ($v) => !is_null($v)));
        }

        return $account->fresh();
    }

    // ─────────────────────────────────────────────────────────────
    // القيود اليومية
    // ─────────────────────────────────────────────────────────────

    /**
     * إنشاء قيد يومي مزدوج
     *
     * @param array $data [
     *   'entry_date'  => 'Y-m-d',
     *   'description' => 'بيان القيد',
     *   'source_type' => 'manual|sales_invoice|...',
     *   'source_id'   => 123,
     *   'notes'       => '...',
     *   'lines'       => [
     *     ['account_id' => 1, 'debit' => 1000, 'credit' => 0, 'description' => '...'],
     *     ['account_id' => 2, 'debit' => 0, 'credit' => 1000, 'description' => '...'],
     *   ]
     * ]
     */
    public function createJournalEntry(array $data, User $user): JournalEntry
    {
        return DB::transaction(function () use ($data, $user) {
            $lines = $data['lines'] ?? [];

            // ── التحقق الأساسي من السطور ──
            $this->validateLines($lines);

            $totalDebit  = collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0));
            $totalCredit = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));

            // ── التحقق من التوازن ──
            if (round($totalDebit - $totalCredit, 4) !== 0.0) {
                throw ValidationException::withMessages([
                    'lines' => sprintf(
                        'القيد غير متوازن: إجمالي المدين (%.4f) لا يساوي إجمالي الدائن (%.4f).',
                        $totalDebit,
                        $totalCredit
                    ),
                ]);
            }

            $entryNumber = $this->generateEntryNumber();
            $fiscalYear  = (int) Carbon::parse($data['entry_date'])->format('Y');

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'entry_date'   => $data['entry_date'],
                'source_type'  => $data['source_type'] ?? 'manual',
                'source_id'    => $data['source_id'] ?? null,
                'description'  => $data['description'],
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'status'       => 'draft',
                'fiscal_year'  => $fiscalYear,
                'created_by'   => $user->id,
                'notes'        => $data['notes'] ?? null,
            ]);

            // ── إنشاء سطور القيد ──
            foreach ($lines as $index => $line) {
                // التحقق من أن الحساب ختامي (يقبل القيود)
                $account = Account::findOrFail($line['account_id']);
                if (!$account->is_posting_account) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.account_id" => "الحساب [{$account->code} - {$account->name_ar}] تجميعي ولا يقبل القيود المباشرة.",
                    ]);
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $line['account_id'],
                    'debit'            => (float) ($line['debit'] ?? 0),
                    'credit'           => (float) ($line['credit'] ?? 0),
                    'description'      => $line['description'] ?? null,
                    'customer_id'      => $line['customer_id'] ?? null,
                    'supplier_id'      => $line['supplier_id'] ?? null,
                    'line_number'      => $index + 1,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    /**
     * ترحيل قيد من مسودة إلى نهائي (مُرحَّل)
     * بعد الترحيل: لا يمكن تعديل القيد، فقط عكسه
     */
    public function postEntry(JournalEntry $entry, User $user): JournalEntry
    {
        if ($entry->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'يمكن ترحيل القيود في مرحلة المسودة فقط.',
            ]);
        }

        if (!$entry->isBalanced()) {
            throw ValidationException::withMessages([
                'lines' => 'لا يمكن ترحيل قيد غير متوازن.',
            ]);
        }

        $entry->update([
            'status'    => 'posted',
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        return $entry->fresh(['lines.account', 'creator', 'poster']);
    }

    /**
     * عكس قيد مُرحَّل (Reversal Entry)
     * يُنشئ قيداً جديداً بنفس السطور مع قلب المدين والدائن
     */
    public function reverseEntry(JournalEntry $entry, User $user, string $reason): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw ValidationException::withMessages([
                'status' => 'يمكن عكس القيود المُرحَّلة فقط.',
            ]);
        }

        if ($entry->reversed_by_entry_id) {
            throw ValidationException::withMessages([
                'status' => 'هذا القيد تم عكسه مسبقاً.',
            ]);
        }

        return DB::transaction(function () use ($entry, $user, $reason) {
            $reversalData = [
                'entry_date'  => now()->toDateString(),
                'description' => "عكس القيد [{$entry->entry_number}] — {$reason}",
                'source_type' => 'adjustment',
                'source_id'   => $entry->id,
                'notes'       => $reason,
                'lines'       => $entry->lines->map(fn ($line) => [
                    'account_id'  => $line->account_id,
                    'debit'       => (float) $line->credit,   // قلب
                    'credit'      => (float) $line->debit,    // قلب
                    'description' => "عكس: {$line->description}",
                    'customer_id' => $line->customer_id,
                    'supplier_id' => $line->supplier_id,
                ])->toArray(),
            ];

            $reversalEntry = $this->createJournalEntry($reversalData, $user);
            $this->postEntry($reversalEntry, $user);

            // تحديث القيد الأصلي بمرجع القيد العاكس
            $entry->update([
                'status'                => 'reversed',
                'reversed_by_entry_id'  => $reversalEntry->id,
            ]);

            return $reversalEntry->fresh(['lines.account']);
        });
    }

    // ─────────────────────────────────────────────────────────────
    // ميزان المراجعة
    // ─────────────────────────────────────────────────────────────

    /**
     * استخراج ميزان المراجعة لفترة زمنية محددة
     */
    public function getTrialBalance(int $fiscalYear, ?string $fromDate = null, ?string $toDate = null): array
    {
        $query = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('chart_of_accounts', 'journal_entry_lines.account_id', '=', 'chart_of_accounts.id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.fiscal_year', $fiscalYear)
            ->select(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name_ar',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
                DB::raw('SUM(journal_entry_lines.debit) as total_debit'),
                DB::raw('SUM(journal_entry_lines.credit) as total_credit'),
            )
            ->groupBy(
                'chart_of_accounts.id',
                'chart_of_accounts.code',
                'chart_of_accounts.name_ar',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance',
            )
            ->orderBy('chart_of_accounts.code');

        if ($fromDate) {
            $query->where('journal_entries.entry_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('journal_entries.entry_date', '<=', $toDate);
        }

        $rows = $query->get();

        $totalDebit  = $rows->sum('total_debit');
        $totalCredit = $rows->sum('total_credit');

        return [
            'fiscal_year'  => $fiscalYear,
            'from_date'    => $fromDate,
            'to_date'      => $toDate,
            'accounts'     => $rows->map(fn ($row) => [
                'id'             => $row->id,
                'code'           => $row->code,
                'name_ar'        => $row->name_ar,
                'account_type'   => $row->account_type,
                'normal_balance' => $row->normal_balance,
                'total_debit'    => (float) $row->total_debit,
                'total_credit'   => (float) $row->total_credit,
                'balance'        => $row->normal_balance === 'debit'
                    ? (float) $row->total_debit - (float) $row->total_credit
                    : (float) $row->total_credit - (float) $row->total_debit,
            ]),
            'totals' => [
                'total_debit'  => (float) $totalDebit,
                'total_credit' => (float) $totalCredit,
                'is_balanced'  => round((float) $totalDebit - (float) $totalCredit, 4) === 0.0,
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // أدوات مساعدة
    // ─────────────────────────────────────────────────────────────

    protected function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'يجب أن يحتوي القيد على سطرين على الأقل.',
            ]);
        }

        foreach ($lines as $i => $line) {
            $debit  = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw ValidationException::withMessages([
                    "lines.{$i}" => "قيم المدين والدائن يجب أن تكون أرقاماً موجبة.",
                ]);
            }

            if ($debit === 0.0 && $credit === 0.0) {
                throw ValidationException::withMessages([
                    "lines.{$i}" => "السطر {$i} فارغ: يجب أن يحتوي على قيمة مدين أو دائن.",
                ]);
            }

            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages([
                    "lines.{$i}" => "لا يمكن أن يكون السطر مديناً ودائناً في آنٍ واحد.",
                ]);
            }

            if (empty($line['account_id'])) {
                throw ValidationException::withMessages([
                    "lines.{$i}.account_id" => "يجب تحديد الحساب لكل سطر.",
                ]);
            }
        }
    }

    protected function generateEntryNumber(): string
    {
        $year  = date('Y');
        $count = JournalEntry::whereYear('created_at', $year)->count() + 1;
        return sprintf('JE-%s-%05d', $year, $count);
    }
}
