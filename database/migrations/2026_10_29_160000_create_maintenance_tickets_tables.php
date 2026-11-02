<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 64)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('customer_device_id')->constrained('customer_devices');
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses');
            $table->enum('service_type', [
                'routine_filter_change',
                'repair',
                'installation',
                'relocation',
                'warranty_check'
            ])->default('routine_filter_change');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', [
                'new',
                'under_review',
                'waiting_customer_confirmation',
                'scheduled',
                'assigned',
                'in_progress',
                'waiting_parts',
                'completed_pending_review',
                'approved_closed',
                'postponed',
                'unreachable',
                'cancelled',
                'returned_for_rework'
            ])->default('new')->index();
            $table->foreignId('technician_id')->nullable()->constrained('users');
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->date('scheduled_date')->nullable()->index();
            $table->string('time_slot', 50)->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('total_cost', 12, 4)->default(0.0000);
            $table->text('rework_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_spare_parts_used', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 4)->default(0.0000);
            $table->unsignedTinyInteger('stage_number')->nullable();
            $table->boolean('is_warranty_covered')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_spare_parts_used');
        Schema::dropIfExists('maintenance_tickets');
    }
};
