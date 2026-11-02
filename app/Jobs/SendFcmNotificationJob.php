<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * مهمة إرسال إشعار FCM في الخلفية عبر Queue Worker.
 *
 * تلتزم بالمواصفات المعمارية:
 * - $tries = 3 محاولات كحد أقصى.
 * - $backoff = [60, 300, 900] ثانية بين المحاولات.
 * - فحص مسبق لحالة السجل قبل إعادة المحاولة.
 */
class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد المحاولات الأقصى قبل اعتبار المهمة فاشلة.
     */
    public int $tries = 3;

    /**
     * فترات الانتظار بين المحاولات (بالثواني): دقيقة، 5 دقائق، 15 دقيقة.
     */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
        public readonly string $idempotencyKey = ''
    ) {}

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning("SendFcmNotificationJob: المستخدم #{$this->userId} غير موجود. تم تخطي الإشعار.");
            return;
        }

        // فحص مسبق: هل تم إرسال هذا الإشعار بالفعل؟
        if (! empty($this->idempotencyKey) && NotificationLog::isDuplicate($this->idempotencyKey)) {
            Log::info("SendFcmNotificationJob: الإشعار مكرر بمفتاح {$this->idempotencyKey}. تم التخطي.");
            return;
        }

        $notificationService->sendToUser(
            user: $user,
            type: $this->type,
            title: $this->title,
            body: $this->body,
            data: $this->data,
            idempotencyKey: $this->idempotencyKey
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendFcmNotificationJob: فشل نهائي في إرسال إشعار للمستخدم #{$this->userId}. الخطأ: {$exception->getMessage()}");

        // تحديث سجل الإشعار إلى حالة الفشل إذا وُجد
        if (! empty($this->idempotencyKey)) {
            NotificationLog::where('idempotency_key', $this->idempotencyKey)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'last_attempted_at' => now(),
                ]);
        }
    }
}
