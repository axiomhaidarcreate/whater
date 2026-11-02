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
        Schema::create('device_models', function (Blueprint $table) {
            $table->id();
            $table->string('model_code', 64)->unique()->index();
            $table->string('name', 191);
            $table->unsignedTinyInteger('stages_count')->default(6);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('device_model_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_model_id')->constrained('device_models')->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->string('stage_name_ar', 100);
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('default_lifespan_days');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();

            $table->unique(['device_model_id', 'stage_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_model_stages');
        Schema::dropIfExists('device_models');
    }
};
