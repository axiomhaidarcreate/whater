<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_interactions', function (Blueprint $table) {
            $table->string('channel', 50)->default('phone_call')->change();
            $table->string('outcome', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_interactions', function (Blueprint $table) {
            $table->enum('channel', ['phone_call', 'whatsapp', 'in_person', 'system_generated'])->default('phone_call')->change();
            $table->enum('outcome', [
                'agreed_scheduled',
                'postponed',
                'no_answer',
                'phone_off',
                'refused_service',
                'wrong_number',
                'called_back'
            ])->change();
        });
    }
};

