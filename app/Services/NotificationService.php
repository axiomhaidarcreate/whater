<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * خدمة الإشعارات المركزية - تتولى إرسال إشعارات FCM مع منع التكرار عبر مفتاح التماثل.
 *
 * تتبع استراتيجية منع التكرار (Idempotency) المحددة في ARCHITECTURE.md:
 * 1. توليد مفتاح تماثل فريد (Idempotency Key).
 * 2. فحص جدول notification_logs قبل الإرسال.
 * 3. تسجيل كل محاولة إرسال في السجل.
 */
class NotificationService
{
    /**
     * FCM HTTP v1 API endpoint.
     * في بيئة الإنتاج يُستبدل بـ Google OAuth2 + projects/{id}/messages:send
     * في بيئة الاختبار يُستخدم FCM Legacy HTTP API مع Server Key.
     */
    private const FCM_LEGACY_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';

    /**
     * إرسال إشعار FCM لمستخدم واحد مع منع التكرار.
     *
     * @param User $user المستخدم المستهدف
     * @param string $type نوع الإشعار (due_reminder, ticket_assigned, إلخ)
     * @param string $title عنوان الإشعار
     * @param string $body محتوى الإشعار
     * @param array $data بيانات إضافية
     * @param string $idempotencyKey مفتاح التماثل لمنع التكرار
     * @return NotificationLog|null سجل الإشعار أو null إذا تم تخطيه (مكرر)
     */
    public function sendToUser(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        string $idempotencyKey = ''
    ): ?NotificationLog {
        // توليد مفتاح تماثل إذا لم يُوفّر
        if (empty($idempotencyKey)) {
            $idempotencyKey = hash('sha256', "{$type}:{$user->id}:" . now()->toDateString() . ':' . json_encode($data));
        }

        // فحص التكرار - إذا سبق الإرسال بنجاح أو قيد الانتظار، تخطي
        if (NotificationLog::isDuplicate($idempotencyKey)) {
            Log::info("تم تخطي إشعار مكرر للمستخدم {$user->id} بمفتاح: {$idempotencyKey}");
            return null;
        }

        // إنشاء سجل الإشعار بحالة معلّقة
        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'idempotency_key' => $idempotencyKey,
            'channel' => 'fcm',
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'status' => 'pending',
        ]);

        // جلب توكنات FCM النشطة للمستخدم (آخر 30 يوم نشاط)
        $tokens = $this->getActiveTokens($user);

        if ($tokens->isEmpty()) {
            $log->update([
                'status' => 'skipped',
                'error_message' => 'لا توجد أجهزة مسجلة نشطة للمستخدم.',
            ]);
            Log::info("تم تخطي إشعار للمستخدم {$user->id}: لا أجهزة مسجلة.");
            return $log;
        }

        // محاولة الإرسال عبر FCM
        $success = $this->dispatchFcm($tokens->pluck('fcm_token')->toArray(), $title, $body, $data);

        $log->recordAttempt($success, $success ? null : 'فشل في إرسال FCM.');

        return $log;
    }

    /**
     * إرسال إشعار جماعي لعدة مستخدمين مع منع التكرار لكل واحد.
     *
     * @param \Illuminate\Support\Collection $users مجموعة المستخدمين
     * @param string $type نوع الإشعار
     * @param string $title عنوان الإشعار
     * @param string $body محتوى الإشعار
     * @param array $data بيانات إضافية
     * @param callable|null $keyGenerator دالة توليد مفتاح التماثل لكل مستخدم
     * @return array إحصائيات الإرسال ['sent' => n, 'skipped' => n, 'failed' => n]
     */
    public function sendToMany(
        $users,
        string $type,
        string $title,
        string $body,
        array $data = [],
        ?callable $keyGenerator = null
    ): array {
        $stats = ['sent' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($users as $user) {
            $key = $keyGenerator ? $keyGenerator($user) : '';

            $log = $this->sendToUser($user, $type, $title, $body, $data, $key);

            if ($log === null) {
                $stats['skipped']++;
            } elseif ($log->status === 'sent') {
                $stats['sent']++;
            } elseif ($log->status === 'skipped') {
                $stats['skipped']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * إرسال إشعار عند إسناد تذكرة لفني.
     */
    public function notifyTicketAssigned(int $ticketId, User $technician, string $customerName): ?NotificationLog
    {
        $key = NotificationLog::generateTicketKey($ticketId, 'ticket_assigned');

        return $this->sendToUser(
            user: $technician,
            type: 'ticket_assigned',
            title: 'مهمة صيانة جديدة 🔧',
            body: "تم إسناد مهمة صيانة جديدة لك للعميل: {$customerName}. يرجى مراجعة التفاصيل.",
            data: [
                'ticket_id' => $ticketId,
                'action' => 'open_ticket',
            ],
            idempotencyKey: $key
        );
    }

    /**
     * إرسال إشعار عند اعتماد التذكرة من المشرف.
     */
    public function notifyTicketApproved(int $ticketId, User $technician): ?NotificationLog
    {
        $key = NotificationLog::generateTicketKey($ticketId, 'ticket_approved');

        return $this->sendToUser(
            user: $technician,
            type: 'ticket_approved',
            title: 'تمت الموافقة على المهمة ✅',
            body: 'تمت الموافقة على مهمتك واعتمادها من المشرف بنجاح. أحسنت!',
            data: [
                'ticket_id' => $ticketId,
                'action' => 'open_ticket',
            ],
            idempotencyKey: $key
        );
    }

    /**
     * إرسال إشعار عند إعادة التذكرة للتصحيح.
     */
    public function notifyTicketRework(int $ticketId, User $technician, string $reason): ?NotificationLog
    {
        $key = NotificationLog::generateTicketKey($ticketId, 'ticket_rework');

        return $this->sendToUser(
            user: $technician,
            type: 'ticket_rework',
            title: 'مطلوب تصحيح المهمة ⚠️',
            body: "أعاد المشرف المهمة للتصحيح. السبب: {$reason}",
            data: [
                'ticket_id' => $ticketId,
                'action' => 'open_ticket',
            ],
            idempotencyKey: $key
        );
    }

    /**
     * إرسال تذكير استحقاق فلتر للعميل.
     */
    public function notifyFilterDueReminder(
        User $customerUser,
        int $deviceStageId,
        string $dueDate,
        string $filterName,
        string $deviceName
    ): ?NotificationLog {
        $key = NotificationLog::generateDueReminderKey($deviceStageId, $dueDate, 'fcm');

        return $this->sendToUser(
            user: $customerUser,
            type: 'due_reminder',
            title: 'تذكير بموعد صيانة الفلتر 💧',
            body: "يقترب موعد استبدال {$filterName} لجهاز {$deviceName}. موعد الاستحقاق: {$dueDate}",
            data: [
                'device_stage_id' => $deviceStageId,
                'due_date' => $dueDate,
                'action' => 'view_device',
            ],
            idempotencyKey: $key
        );
    }

    // ─── الدوال الداخلية (Private Methods) ──────────────────

    /**
     * جلب توكنات FCM النشطة للمستخدم (آخر 30 يوم).
     */
    private function getActiveTokens(User $user)
    {
        return UserDeviceToken::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where('last_seen_at', '>=', now()->subDays(30))
                  ->orWhereNull('last_seen_at');
            })
            ->get();
    }

    /**
     * إرسال FCM عبر HTTP Legacy API.
     * في بيئة الاختبار يتم محاكاة النجاح دائماً عبر متغير البيئة FCM_ENABLED.
     *
     * @param array $tokens قائمة توكنات FCM
     * @param string $title عنوان الإشعار
     * @param string $body محتوى الإشعار
     * @param array $data بيانات إضافية
     * @return bool true إذا نجح الإرسال
     */
    private function dispatchFcm(array $tokens, string $title, string $body, array $data): bool
    {
        // في بيئة الاختبار أو عند عدم تفعيل FCM، نُحاكي النجاح
        if (! config('services.fcm.enabled', false)) {
            Log::info("FCM (محاكاة): إرسال إشعار '{$title}' إلى " . count($tokens) . ' جهاز.');
            return true;
        }

        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey)) {
            Log::error('FCM Server Key غير مُعرّف. تحقق من إعدادات services.fcm.server_key');
            return false;
        }

        try {
            $payload = [
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post(self::FCM_LEGACY_ENDPOINT, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info("FCM: تم إرسال {$result['success']} من " . count($tokens) . ' إشعار بنجاح.');
                return ($result['success'] ?? 0) > 0;
            }

            Log::error('FCM: فشل الإرسال. HTTP ' . $response->status() . ': ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('FCM: خطأ أثناء الإرسال: ' . $e->getMessage());
            return false;
        }
    }
}
