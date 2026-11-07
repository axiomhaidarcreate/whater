<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('vendor_invoice_number', 100)->nullable()->after('notes');
            $table->string('attachment_path', 500)->nullable()->after('vendor_invoice_number');
            $table->decimal('tax_amount', 12, 4)->default(0)->after('total_amount');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('tax_percent', 5, 2)->default(0)->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['vendor_invoice_number', 'attachment_path', 'tax_amount']);
        });
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('tax_percent');
        });
    }
};
