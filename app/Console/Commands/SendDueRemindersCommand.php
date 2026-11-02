<?php

namespace App\Console\Commands;

use App\Jobs\SendFcmNotificationJob;
use App\Models\CustomerDeviceStage;
use App\Models\MaintenanceTicket;
use App\Models\NotificationLog;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * أمر إرسال تذكيرات استحقاق الفلاتر اليومية.
 *
 * يُجدول للتشغيل يومياً في الساعة 08:00 صباحاً عبر Laravel Scheduler.
 * يتبع خوارزمية منع التكرار والتكامل الموضحة في ARCHITECTURE.md قسم 3.2.
 */
class SendDueRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'filters:send-due-reminders
                            {--days=14 : نافذة التنبيه بالأيام قبل الاستحقاق}
                            {--dry-run : تشغيل بدون إرسال فعلي لمعاينة النتائج}';

    /**
     * The console command description.
     */
    protected $description = 'إرسال تذكيرات استحقاق الفلاتر اليومية للعملاء الذين تقترب مواعيد صيانتهم';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = microtime(true);
        $notificationWindowDays = (int) $this->option('days');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('──────────────────────────────────────────────────────');
        $this->info("🔔 بدء تشغيل أمر تذكيرات استحقاق الفلاتر - " . now()->toDateTimeString());
        $this->info("نافذة التنبيه: {$notificationWindowDays} يوماً قبل الاستحقاق");
        if ($isDryRun) {
            $this->warn('⚠️  وضع المعاينة (Dry Run): لن يتم إرسال أي إشعارات فعلية');
        }
        $this->info('──────────────────────────────────────────────────────');

        $stats = [
            'stages_checked' => 0,
            'reminders_queued' => 0,
            'skipped_duplicate' => 0,
            'skipped_open_ticket' => 0,
            'skipped_postponed' => 0,
            'skipped_no_user' => 0,
            'errors' => 0,
        ];

        $today = Carbon::today();
        $windowEnd = $today->copy()->addDays($notificationWindowDays);

        // ─── جلب المراحل المستحقة أو القريبة من الاستحقاق ─────────────────
        // استعلام بكفاءة مع Eager Loading لتجنب N+1
        $dueStages = CustomerDeviceStage::query()
            ->with([
                'device.customer.user',
                'device.model',
            ])
            ->whereIn('status', ['not_due', 'upcoming', 'due_today', 'overdue'])
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<=', $windowEnd)
            ->whereNull('postponed_until') // غير مؤجلة
            ->orderBy('next_due_at')
            ->get();

        $stats['stages_checked'] = $dueStages->count();

        $this->info("تم العثور على {$stats['stages_checked']} مرحلة تستحق تذكيراً.");
        $this->newLine();

        foreach ($dueStages as $stage) {
            try {
                $this->processStage($stage, $today, $isDryRun, $stats);
            } catch (\Throwable $e) {
                $stats['errors']++;
                Log::error("SendDueRemindersCommand: خطأ في معالجة المرحلة #{$stage->id}: " . $e->getMessage());
                $this->error("  خطأ في المرحلة #{$stage->id}: " . $e->getMessage());
            }
        }

        // ─── تحديث حالات المراحل الإجمالية ─────────────────────────────────
        if (! $isDryRun) {
            $this->updateStageStatuses($today);
        }

        // ─── إحصائيات النتائج ─────────────────────────────────────────────
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->newLine();
        $this->info('──────────────────────────────────────────────────────');
        $this->info("✅ اكتمل الأمر في {$elapsed} ثانية");
        $this->table(
            ['الإجراء', 'العدد'],
            [
                ['مراحل تم فحصها', $stats['stages_checked']],
                ['تذكيرات مُضافة للطابور', $stats['reminders_queued']],
                ['مكررة (تم تخطيها)', $stats['skipped_duplicate']],
                ['تذاكر مفتوحة (تم تخطيها)', $stats['skipped_open_ticket']],
                ['مؤجلة (تم تخطيها)', $stats['skipped_postponed']],
                ['بدون حساب مستخدم (تم تخطيها)', $stats['skipped_no_user']],
                ['أخطاء', $stats['errors']],
            ]
        );
        $this->info('──────────────────────────────────────────────────────');

        Log::info('SendDueRemindersCommand: اكتمل', $stats);

        return self::SUCCESS;
    }

    /**
     * معالجة مرحلة واحدة وإرسال التذكير المناسب.
     */
    private function processStage(
        CustomerDeviceStage $stage,
        Carbon $today,
        bool $isDryRun,
        array &$stats
    ): void {
        $device = $stage->device;
        $customer = $device?->customer ?? null;
        $user = $customer?->user ?? null;

        $dueDate = Carbon::parse($stage->next_due_at)->toDateString();
        $deviceName = $device?->model?->name_ar ?? "جهاز #{$device?->id}";
        $filterName = $stage->stage_name_ar ?? "مرحلة {$stage->stage_number}";

        // 1. تخطي إذا كان العميل بدون حساب مستخدم
        if (! $user) {
            $stats['skipped_no_user']++;
            $this->line("  ⬜ [{$stage->id}] {$filterName}: بدون حساب مستخدم. تم التخطي.");
            return;
        }

        // 2. تخطي إذا كانت المرحلة مؤجلة ولم يحن تاريخ التأجيل بعد
        if ($stage->postponed_until && Carbon::parse($stage->postponed_until)->gt($today)) {
            $stats['skipped_postponed']++;
            $this->line("  🔵 [{$stage->id}] {$filterName} للعميل {$user->name}: مؤجلة حتى {$stage->postponed_until}.");
            return;
        }

        // 3. تخطي إذا كان هناك تذكرة صيانة مفتوحة لنفس الجهاز
        // (منع التكرار والإزعاج كما هو موضح في ARCHITECTURE.md)
        $hasOpenTicket = MaintenanceTicket::where('customer_device_id', $device->id)
            ->whereIn('status', ['new', 'scheduled', 'assigned', 'in_progress', 'waiting_parts'])
            ->exists();

        if ($hasOpenTicket) {
            $stats['skipped_open_ticket']++;
            $this->line("  🟡 [{$stage->id}] {$filterName} للعميل {$user->name}: تذكرة مفتوحة قائمة. تم التخطي.");
            return;
        }

        // 4. توليد مفتاح التماثل والتحقق من التكرار
        $idempotencyKey = NotificationLog::generateDueReminderKey($stage->id, $dueDate, 'fcm');

        if (NotificationLog::isDuplicate($idempotencyKey)) {
            $stats['skipped_duplicate']++;
            $this->line("  🟠 [{$stage->id}] {$filterName} للعميل {$user->name}: إشعار مكرر (أُرسل مسبقاً). تم التخطي.");
            return;
        }

        // 5. تحديد الرسالة بناءً على وضع الاستحقاق
        $daysRemaining = $today->diffInDays(Carbon::parse($stage->next_due_at), false);
        [$title, $body] = $this->buildMessage($filterName, $deviceName, $dueDate, $daysRemaining);

        $this->line("  ✅ [{$stage->id}] {$filterName} - {$user->name} ({$daysRemaining} يوم) → إضافة للطابور");

        if (! $isDryRun) {
            // إضافة مهمة الإشعار لطابور الخلفية
            SendFcmNotificationJob::dispatch(
                userId: $user->id,
                type: 'due_reminder',
                title: $title,
                body: $body,
                data: [
                    'device_stage_id' => $stage->id,
                    'customer_device_id' => $device->id,
                    'due_date' => $dueDate,
                    'action' => 'view_device',
                ],
                idempotencyKey: $idempotencyKey
            );
        }

        $stats['reminders_queued']++;
    }

    /**
     * بناء رسالة التذكير بناءً على عدد الأيام المتبقية.
     */
    private function buildMessage(
        string $filterName,
        string $deviceName,
        string $dueDate,
        int $daysRemaining
    ): array {
        if ($daysRemaining < 0) {
            return [
                'موعد الصيانة تجاوز المدة المحددة ⏰',
                "تأخر موعد استبدال {$filterName} لجهاز {$deviceName} بمقدار " . abs($daysRemaining) . " يوم. يرجى التواصل معنا لجدولة الصيانة.",
            ];
        }

        if ($daysRemaining === 0) {
            return [
                'موعد صيانة اليوم 💧',
                "موعد استبدال {$filterName} لجهاز {$deviceName} هو اليوم! يرجى التواصل معنا لتحديد وقت الزيارة.",
            ];
        }

        if ($daysRemaining <= 3) {
            return [
                "تذكير عاجل: صيانة خلال {$daysRemaining} أيام 🔔",
                "موعد استبدال {$filterName} لجهاز {$deviceName} بعد {$daysRemaining} أيام ({$dueDate}). احجز موعدك الآن!",
            ];
        }

        return [
            "تذكير صيانة دورية 💧",
            "يقترب موعد استبدال {$filterName} لجهاز {$deviceName} في {$dueDate} ({$daysRemaining} يوماً). نتطلع لخدمتك!",
        ];
    }

    /**
     * تحديث حالات المراحل بناءً على تاريخ اليوم (upcoming/due_today/overdue).
     */
    private function updateStageStatuses(Carbon $today): void
    {
        $this->info('تحديث حالات مراحل الفلاتر...');

        $notificationWindowDays = (int) $this->option('days');

        // overdue: تجاوز موعد الاستحقاق ولم تُستبدل
        $overdueCount = CustomerDeviceStage::whereIn('status', ['not_due', 'upcoming', 'due_today'])
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '<', $today)
            ->update(['status' => 'overdue']);

        // due_today: موعد الاستحقاق اليوم
        $dueTodayCount = CustomerDeviceStage::whereIn('status', ['not_due', 'upcoming', 'overdue'])
            ->whereNotNull('next_due_at')
            ->whereDate('next_due_at', $today)
            ->update(['status' => 'due_today']);

        // upcoming: ضمن نافذة التنبيه
        $upcomingCount = CustomerDeviceStage::where('status', 'not_due')
            ->whereNotNull('next_due_at')
            ->whereBetween('next_due_at', [$today->copy()->addDay(), $today->copy()->addDays($notificationWindowDays)])
            ->update(['status' => 'upcoming']);

        $this->info("  تحديثات: {$overdueCount} متأخرة، {$dueTodayCount} مستحقة اليوم، {$upcomingCount} قادمة قريباً.");
    }
}
