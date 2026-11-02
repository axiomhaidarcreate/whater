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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->index();
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('sku', 64)->unique()->index();
            $table->string('barcode', 64)->nullable()->unique()->index();
            $table->string('name_ar', 191);
            $table->string('name_en', 191)->nullable();
            $table->enum('type', [
                'water_device',
                'filter_cartridge',
                'membrane',
                'pump',
                'spare_part',
                'service'
            ])->index();
            $table->string('unit', 32)->default('piece');
            $table->decimal('cost_price', 12, 4)->default(0.0000);
            $table->decimal('selling_price', 12, 4)->default(0.0000);
            $table->unsignedInteger('reorder_level')->default(5);
            $table->boolean('is_active')->default(true)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
