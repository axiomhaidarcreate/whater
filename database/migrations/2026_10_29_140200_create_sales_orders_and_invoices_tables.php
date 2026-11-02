<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. أوامر البيع وعروض الأسعار
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 64)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->enum('status', ['draft', 'confirmed', 'invoiced', 'cancelled'])->default('draft')->index();
            $table->decimal('subtotal', 12, 4)->default(0.0000);
            $table->decimal('discount_amount', 12, 4)->default(0.0000);
            $table->decimal('tax_amount', 12, 4)->default(0.0000);
            $table->decimal('grand_total', 12, 4)->default(0.0000);
            $table->date('order_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('total_price', 12, 4);
            $table->timestamps();
        });

        // 2. الفواتير المالية
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 64)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses');
            $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            $table->unsignedBigInteger('maintenance_ticket_id')->nullable()->index();
            $table->date('invoice_date');
            $table->decimal('subtotal', 12, 4)->default(0.0000);
            $table->decimal('discount_amount', 12, 4)->default(0.0000);
            $table->decimal('tax_amount', 12, 4)->default(0.0000);
            $table->decimal('grand_total', 12, 4)->default(0.0000);
            $table->decimal('paid_amount', 12, 4)->default(0.0000);
            $table->decimal('balance_due', 12, 4)->default(0.0000);
            $table->enum('status', [
                'draft',
                'issued',
                'partially_paid',
                'paid',
                'cancelled',
                'refunded'
            ])->default('draft')->index();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 4);
            $table->decimal('discount_amount', 12, 4)->default(0.0000);
            $table->decimal('tax_amount', 12, 4)->default(0.0000);
            $table->decimal('total_price', 12, 4);
            $table->string('serial_number', 100)->nullable();
            $table->timestamps();
        });

        // 3. سندات القبض والمدفوعات
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 64)->unique()->index();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            $table->decimal('amount', 12, 4);
            $table->enum('payment_method', [
                'cash',
                'mada_pos',
                'credit_card',
                'bank_transfer',
                'online_gateway'
            ]);
            $table->string('transaction_reference', 100)->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
