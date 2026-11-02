<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * جدول سجل الإشعارات - يمنع تكرار إرسال نفس الإشعار عبر مفتاح التماثل الفريد (Idempotency Key).
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // نوع الإشعار: due_reminder, ticket_assigned, ticket_completed, system_alert
            $table->string('type', 50)->index();

            // مفتاح التماثل الفريد لمنع التكرار
            // hash(device_stage_id + due_date + channel) أو hash(ticket_id + type)
            $table->string('idempotency_key', 64)->unique();

            // القناة: fcm, sms, database
            $table->enum('channel', ['fcm', 'sms', 'database'])->default('fcm');

            // عنوان ومحتوى الإشعار
            $table->string('title');
            $table->text('body');

            // بيانات إضافية مرتبطة بالإشعار (JSON)
            $table->json('data')->nullable();

            // حالة الإرسال
            $table->enum('status', ['pending', 'sent', 'failed', 'skipped'])->default('pending');

            // رسالة الخطأ في حال الفشل
            $table->text('error_message')->nullable();

            // عدد محاولات الإرسال
            $table->unsignedTinyInteger('attempts')->default(0);

            // تاريخ آخر محاولة
            $table->timestamp('last_attempted_at')->nullable();

            // تاريخ الإرسال الناجح
            $table->timestamp('sent_at')->nullable();

            $table->timestamps();

            // فهارس مركبة للبحث والتصفية
            $table->index(['user_id', 'type', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
