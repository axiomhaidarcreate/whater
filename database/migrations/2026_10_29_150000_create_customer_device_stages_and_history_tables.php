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
        // 1. مراحل وشمعات كل جهاز لدى العميل (Independent Filter Stages)
        Schema::create('customer_device_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_device_id')->constrained('customer_devices')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->string('stage_name_ar', 100);
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('service_interval_days');
            $table->unsignedTinyInteger('service_interval_months')->nullable();
            $table->date('last_actual_replacement_at');
            $table->date('next_due_at')->index();
            $table->enum('status', [
                'not_due',
                'upcoming',
                'due_today',
                'overdue',
                'replaced',
                'postponed',
                'exempted'
            ])->default('not_due')->index();
            $table->date('postponed_until')->nullable();
            $table->text('exempted_reason')->nullable();
            $table->foreignId('exempted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('last_ticket_id')->nullable()->index();
            $table->text('technician_notes')->nullable();
            $table->timestamps();

            $table->unique(['customer_device_id', 'stage_number']);
        });

        // 2. سجل الاستبدالات التاريخي غير القابل للتعديل (Replacement Audit History)
        Schema::create('stage_replacement_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_device_stage_id')->constrained('customer_device_stages')->cascadeOnDelete();
            $table->foreignId('customer_device_id')->constrained('customer_devices')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->unsignedBigInteger('maintenance_ticket_id')->nullable()->index();
            $table->foreignId('technician_id')->constrained('users');
            $table->foreignId('old_product_id')->constrained('products');
            $table->foreignId('new_product_id')->constrained('products');
            $table->date('replacement_date');
            $table->unsignedBigInteger('stock_movement_item_id')->nullable()->index();
            $table->foreignId('approved_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stage_replacement_history');
        Schema::dropIfExists('customer_device_stages');
    }
};
