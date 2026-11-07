<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technician_trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('maintenance_tickets')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('vehicle_description')->nullable()->comment('رقم لوحة السيارة / اسمها');
            $table->string('destination_address')->comment('العنوان / الوجهة');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('odometer_start', 10, 2)->nullable()->comment('عداد الكيلومتر عند المغادرة');
            $table->decimal('odometer_end', 10, 2)->nullable()->comment('عداد الكيلومتر عند العودة');
            $table->decimal('km_traveled', 10, 2)->nullable()->comment('إجمالي المسافة (كم)');
            $table->dateTime('departure_time')->comment('وقت المغادرة');
            $table->dateTime('return_time')->nullable()->comment('وقت العودة');
            $table->text('work_performed')->nullable()->comment('وصف الأعمال المنجزة');
            $table->text('notes')->nullable();
            $table->enum('status', ['dispatched', 'on_trip', 'returned', 'cancelled'])->default('dispatched');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('technician_trips');
    }
};

