<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        // إضافة عداد الصلاحية لكل صنف
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('filter_life_months')->default(0)->after('description')
                  ->comment('صلاحية الفلتر بالأشهر (0 = غير محدد)');
            $table->unsignedInteger('filter_life_liters')->default(0)->after('filter_life_months')
                  ->comment('صلاحية الفلتر بالليترات (0 = غير محدد)');
            $table->enum('reminder_type', ['months', 'liters', 'both'])->default('months')->after('filter_life_liters')
                  ->comment('نوع العداد المستخدم للتذكير');
            $table->string('filter_life_note', 255)->nullable()->after('reminder_type')
                  ->comment('ملاحظة إضافية للعداد');
        });

        // إضافة موعد التذكير وأعمدة المتابعة للفاتورة
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('reminder_date')->nullable()->after('invoice_date')
                  ->comment('موعد تذكير العميل بالصيانة / تغيير الفلاتر');
            $table->boolean('reminder_sent')->default(false)->after('reminder_date');
            $table->timestamp('reminder_sent_at')->nullable()->after('reminder_sent');
            $table->string('reminder_notes', 500)->nullable()->after('reminder_sent_at');
        });

        // جدول مواعيد التذكير التفصيلية (لكل صنف في الفاتورة)
        Schema::create('invoice_item_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('component_name', 191)->nullable()->comment('اسم جزء الصنف المركب');
            $table->date('reminder_date');
            $table->unsignedSmallInteger('life_months')->default(0);
            $table->unsignedInteger('life_liters')->default(0);
            $table->enum('reminder_type', ['months', 'liters', 'both'])->default('months');
            $table->boolean('sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;

        Schema::dropIfExists('invoice_item_reminders');

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['reminder_date', 'reminder_sent', 'reminder_sent_at', 'reminder_notes']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['filter_life_months', 'filter_life_liters', 'reminder_type', 'filter_life_note']);
        });
    }
};

