<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * يُنشئ هذا الـ migration ثلاثة جداول محاسبية:
     *  1. chart_of_accounts  → شجرة الحسابات الهرمية
     *  2. journal_entries    → القيود اليومية (رأس القيد)
     *  3. journal_entry_lines → سطور القيد المزدوج (مدين / دائن)
     */
    public function up(): void
    {
        // ─────────────────────────────────────────────────────────
        // 1. شجرة الحسابات (Chart of Accounts)
        //    هيكل هرمي: الفئة الكبرى → المجموعة → الحساب الفرعي
        // ─────────────────────────────────────────────────────────
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();

            // رمز الحساب (مثال: 1000، 1100، 1110)
            $table->string('code', 20)->unique()->index();

            // اسم الحساب عربي وإنجليزي
            $table->string('name_ar', 191);
            $table->string('name_en', 191)->nullable();

            // نوع الحساب الرئيسي (الفئة الكبرى)
            $table->enum('account_type', [
                'asset',       // أصول
                'liability',   // خصوم
                'equity',      // حقوق الملكية
                'revenue',     // إيرادات
                'expense',     // مصروفات
            ])->index();

            // الطبيعة الطبيعية للحساب (للتحقق من صحة القيود)
            $table->enum('normal_balance', ['debit', 'credit']);

            // الحساب الأب (للهيكل الهرمي) — null = حساب جذر
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('chart_of_accounts')
                ->nullOnDelete();

            // مستوى الحساب في الشجرة (1=فئة، 2=مجموعة، 3=حساب فرعي)
            $table->unsignedTinyInteger('level')->default(1);

            // هل هو حساب ختامي (يقبل القيود) أم تجميعي فقط؟
            $table->boolean('is_posting_account')->default(true);

            // هل الحساب نظامي (لا يُحذف) أم مُضاف يدوياً؟
            $table->boolean('is_system')->default(false);

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['account_type', 'is_active']);
            $table->index(['parent_id', 'level']);
        });

        // ─────────────────────────────────────────────────────────
        // 2. القيود اليومية — رأس القيد (Journal Entries Header)
        // ─────────────────────────────────────────────────────────
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            // رقم القيد (JE-2026-0001)
            $table->string('entry_number', 64)->unique()->index();

            // تاريخ القيد
            $table->date('entry_date')->index();

            // نوع القيد — لربطه بالوحدات الأخرى
            $table->enum('source_type', [
                'manual',          // قيد يدوي
                'sales_invoice',   // من فاتورة مبيعات
                'purchase_invoice',// من فاتورة مشتريات
                'payment_receipt', // من سند قبض
                'payment_voucher', // من سند صرف
                'inventory_adjust',// من تسوية مخزنية
                'opening_balance', // أرصدة افتتاحية
                'depreciation',    // إهلاك
                'adjustment',      // قيد تسوية
            ])->default('manual');

            // ربط القيد بالمستند الأصلي (polymorphic)
            $table->unsignedBigInteger('source_id')->nullable()->index();

            // وصف القيد (البيان)
            $table->string('description', 500);

            // إجمالي المدين = إجمالي الدائن (التوازن)
            $table->decimal('total_debit', 15, 4)->default(0.0000);
            $table->decimal('total_credit', 15, 4)->default(0.0000);

            // حالة القيد
            $table->enum('status', [
                'draft',    // مسودة
                'posted',   // مُرحَّل (نهائي)
                'reversed', // تم عكسه
            ])->default('draft')->index();

            // في حال العكس: رقم القيد العاكس
            $table->unsignedBigInteger('reversed_by_entry_id')->nullable();

            // السنة المالية
            $table->unsignedSmallInteger('fiscal_year');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['fiscal_year', 'entry_date']);
            $table->index(['source_type', 'source_id']);
        });

        // ─────────────────────────────────────────────────────────
        // 3. سطور القيد المزدوج (Journal Entry Lines)
        //    مدين + دائن لكل سطر يجب أن تتوازن في المجموع
        // ─────────────────────────────────────────────────────────
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('chart_of_accounts');

            // مدين أو دائن (ليس كلاهما معاً)
            $table->decimal('debit', 15, 4)->default(0.0000);
            $table->decimal('credit', 15, 4)->default(0.0000);

            // بيان السطر
            $table->string('description', 300)->nullable();

            // ربط اختياري بعميل أو مورد لمتابعة الأرصدة
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('supplier_id')->nullable()->index();

            // ترقيم السطور داخل القيد
            $table->unsignedSmallInteger('line_number')->default(1);

            $table->timestamps();

            $table->index(['journal_entry_id', 'account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
    }
};
