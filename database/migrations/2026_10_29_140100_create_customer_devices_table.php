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
        Schema::create('customer_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_code', 64)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('customer_address_id')->nullable()->constrained('customer_addresses')->nullOnDelete();
            $table->foreignId('device_model_id')->constrained('device_models');
            $table->string('serial_number', 100)->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->nullable()->index();
            $table->date('installation_date')->nullable();
            $table->date('warranty_expiry_date')->nullable();
            $table->enum('status', [
                'pending_installation',
                'active',
                'suspended',
                'decommissioned'
            ])->default('pending_installation')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_devices');
    }
};
