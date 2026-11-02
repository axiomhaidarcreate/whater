<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('customer_device_id')->nullable()->constrained('customer_devices');
            $table->foreignId('agent_id')->constrained('users');
            $table->foreignId('maintenance_ticket_id')->nullable()->constrained('maintenance_tickets')->nullOnDelete();
            $table->enum('channel', ['phone_call', 'whatsapp', 'in_person', 'system_generated'])->default('phone_call');
            $table->enum('outcome', [
                'agreed_scheduled',
                'postponed',
                'no_answer',
                'phone_off',
                'refused_service',
                'wrong_number',
                'called_back'
            ]);
            $table->date('follow_up_date')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_interactions');
    }
};
