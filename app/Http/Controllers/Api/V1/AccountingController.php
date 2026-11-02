<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AccountingController
 * =================================================================
 * يُدير شجرة الحسابات والقيود اليومية المزدوجة وميزان المراجعة.
 *
 * جميع المسارات محمية بـ permission:accounting.view أو accounting.manage
 * =================================================================
 */
class AccountingController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AccountingService $accountingService
    ) {}

    // ─────────────────────────────────────────────────────────────
    // شجرة الحسابات
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/accounting/accounts
     * عرض شجرة الحسابات الهرمية
     */
    public function chartOfAccounts(Request $request): JsonResponse
    {
        $filters = $request->only(['account_type', 'is_active']);
        $tree    = $this->accountingService->getChartOfAccounts($filters);

        return $this->successResponse(
            data: $tree,
            message: 'تم استرجاع شجرة الحسابات بنجاح.'
        );
    }

    /**
     * GET /api/v1/accounting/accounts/posting
     * قائمة الحسابات الختامية فقط (للـ dropdowns في القيود)
     */
    public function postingAccounts(Request $request): JsonResponse
    {
        $accounts = $this->accountingService->getPostingAccounts(
            $request->input('account_type')
        );

        return $this->successResponse(
            data: $accounts,
            message: 'تم استرجاع الحسابات القابلة للترحيل بنجاح.'
        );
    }

    /**
     * GET /api/v1/accounting/accounts/{id}
     * تفاصيل حساب واحد مع رصيده الحالي
     */
    public function showAccount(int $id): JsonResponse
    {
        $account = Account::with(['parent', 'children'])->findOrFail($id);

        return $this->successResponse(
            data: [
                'account'         => $account,
                'current_balance' => $account->getBalance(),
                'children_count'  => $account->children()->count(),
            ],
            message: 'تم استرجاع تفاصيل الحساب بنجاح.'
        );
    }

    /**
     * POST /api/v1/accounting/accounts
     * إضافة حساب جديد لشجرة الحسابات
     */
    public function storeAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'               => ['required', 'string', 'max:20'],
            'name_ar'            => ['required', 'string', 'max:191'],
            'name_en'            => ['nullable', 'string', 'max:191'],
            'account_type'       => ['required', 'in:asset,liability,equity,revenue,expense'],
            'normal_balance'     => ['required', 'in:debit,credit'],
            'parent_id'          => ['nullable', 'exists:chart_of_accounts,id'],
            'is_posting_account' => ['nullable', 'boolean'],
            'is_active'          => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        $account = $this->accountingService->createAccount($validated);

        return $this->successResponse(
            data: $account->load('parent'),
            message: 'تمت إضافة الحساب الجديد بنجاح.',
            statusCode: 201
        );
    }

    /**
     * PUT /api/v1/accounting/accounts/{id}
     * تعديل حساب موجود
     */
    public function updateAccount(Request $request, int $id): JsonResponse
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'code'               => ['sometimes', 'string', 'max:20'],
            'name_ar'            => ['sometimes', 'string', 'max:191'],
            'name_en'            => ['nullable', 'string', 'max:191'],
            'is_posting_account' => ['nullable', 'boolean'],
            'is_active'          => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ]);

        $account = $this->accountingService->updateAccount($account, $validated);

        return $this->successResponse(
            data: $account,
            message: 'تم تحديث الحساب بنجاح.'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // القيود اليومية
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/accounting/journal-entries
     * قائمة القيود اليومية مع فلاتر
     */
    public function indexJournalEntries(Request $request): JsonResponse
    {
        $query = JournalEntry::with(['creator', 'poster'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('fiscal_year')) {
            $query->where('fiscal_year', (int) $request->input('fiscal_year'));
        }

        if ($request->filled('from_date')) {
            $query->where('entry_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('entry_date', '<=', $request->input('to_date'));
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->input('source_type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) =>
                $q->where('entry_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
            );
        }

        $entries = $query->paginate($request->input('per_page', 20));

        return $this->successResponse(
            data: $entries->items(),
            message: 'تم استرجاع القيود اليومية بنجاح.',
            meta: [
                'current_page' => $entries->currentPage(),
                'last_page'    => $entries->lastPage(),
                'per_page'     => $entries->perPage(),
                'total'        => $entries->total(),
            ]
        );
    }

    /**
     * GET /api/v1/accounting/journal-entries/{id}
     * تفاصيل قيد واحد مع سطوره
     */
    public function showJournalEntry(int $id): JsonResponse
    {
        $entry = JournalEntry::with([
            'lines.account',
            'lines.customer',
            'lines.supplier',
            'creator',
            'poster',
            'reversal',
        ])->findOrFail($id);

        return $this->successResponse(
            data: $entry,
            message: 'تم استرجاع تفاصيل القيد بنجاح.'
        );
    }

    /**
     * POST /api/v1/accounting/journal-entries
     * إنشاء قيد يومي جديد (مسودة)
     */
    public function storeJournalEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date'     => ['required', 'date'],
            'description'    => ['required', 'string', 'max:500'],
            'source_type'    => ['nullable', 'in:manual,sales_invoice,purchase_invoice,payment_receipt,payment_voucher,inventory_adjust,opening_balance,depreciation,adjustment'],
            'source_id'      => ['nullable', 'integer'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'lines'          => ['required', 'array', 'min:2'],
            'lines.*.account_id'   => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.debit'        => ['required', 'numeric', 'min:0'],
            'lines.*.credit'       => ['required', 'numeric', 'min:0'],
            'lines.*.description'  => ['nullable', 'string', 'max:300'],
            'lines.*.customer_id'  => ['nullable', 'exists:customers,id'],
            'lines.*.supplier_id'  => ['nullable', 'exists:suppliers,id'],
        ]);

        $entry = $this->accountingService->createJournalEntry($validated, $request->user());

        return $this->successResponse(
            data: $entry,
            message: 'تم إنشاء القيد اليومي بنجاح كمسودة.',
            statusCode: 201
        );
    }

    /**
     * POST /api/v1/accounting/journal-entries/{id}/post
     * ترحيل قيد من مسودة إلى نهائي
     */
    public function postJournalEntry(int $id, Request $request): JsonResponse
    {
        $entry = JournalEntry::with('lines.account')->findOrFail($id);
        $entry = $this->accountingService->postEntry($entry, $request->user());

        return $this->successResponse(
            data: $entry,
            message: 'تم ترحيل القيد بنجاح. لا يمكن تعديله بعد الآن.'
        );
    }

    /**
     * POST /api/v1/accounting/journal-entries/{id}/reverse
     * عكس قيد مُرحَّل
     */
    public function reverseJournalEntry(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $entry   = JournalEntry::with('lines')->findOrFail($id);
        $reversal = $this->accountingService->reverseEntry($entry, $request->user(), $validated['reason']);

        return $this->successResponse(
            data: $reversal,
            message: 'تم عكس القيد وإنشاء قيد التصحيح تلقائياً.'
        );
    }

    // ─────────────────────────────────────────────────────────────
    // التقارير المحاسبية
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /api/v1/accounting/reports/trial-balance
     * ميزان المراجعة
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'from_date'   => ['nullable', 'date'],
            'to_date'     => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $report = $this->accountingService->getTrialBalance(
            (int) $validated['fiscal_year'],
            $validated['from_date'] ?? null,
            $validated['to_date']   ?? null
        );

        return $this->successResponse(
            data: $report,
            message: 'تم استخراج ميزان المراجعة بنجاح.'
        );
    }

    /**
     * GET /api/v1/accounting/accounts/{id}/ledger
     * دفتر الأستاذ: حركات حساب بعينه
     */
    public function accountLedger(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date'   => ['nullable', 'date'],
            'to_date'     => ['nullable', 'date'],
            'fiscal_year' => ['nullable', 'integer'],
        ]);

        $account = Account::findOrFail($id);

        $query = $account->journalLines()
            ->with(['entry', 'customer', 'supplier'])
            ->whereHas('entry', function ($q) use ($validated) {
                $q->where('status', 'posted');
                if (!empty($validated['fiscal_year'])) {
                    $q->where('fiscal_year', $validated['fiscal_year']);
                }
                if (!empty($validated['from_date'])) {
                    $q->where('entry_date', '>=', $validated['from_date']);
                }
                if (!empty($validated['to_date'])) {
                    $q->where('entry_date', '<=', $validated['to_date']);
                }
            })
            ->orderBy('created_at');

        $lines = $query->paginate($request->input('per_page', 50));

        $totalDebit  = (float) $query->sum('debit');
        $totalCredit = (float) $query->sum('credit');

        return $this->successResponse(
            data: $lines->items(),
            message: 'تم استرجاع دفتر أستاذ الحساب بنجاح.',
            meta: [
                'account'      => ['id' => $account->id, 'code' => $account->code, 'name_ar' => $account->name_ar],
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'net_balance'  => $account->getBalance(),
                'current_page' => $lines->currentPage(),
                'last_page'    => $lines->lastPage(),
                'total'        => $lines->total(),
            ]
        );
    }
}
