<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->unsignedInteger('tds_input')->nullable()->after('completed_at');
            $table->unsignedInteger('tds_output')->nullable()->after('tds_input');
            $table->string('customer_signature_path')->nullable()->after('tds_output');
            $table->unsignedTinyInteger('customer_rating')->nullable()->after('customer_signature_path');
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->enum('type', [
                'before_photo',
                'after_photo',
                'old_parts_photo',
                'new_parts_photo',
                'customer_signature',
                'tds_reading_photo'
            ])->index();
            $table->string('file_path', 255);
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 64);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_attachments');

        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropColumn(['tds_input', 'tds_output', 'customer_signature_path', 'customer_rating']);
        });
    }
};
