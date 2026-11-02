<?php

use App\Console\Commands\SendDueRemindersCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks — جداول المهام الآلية لمشروع «تقنية الفلاتر»
|--------------------------------------------------------------------------
|
| يُشغّل Laravel Scheduler عبر Cron Job واحد فقط على الخادم:
|   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
|
*/

// 1. إرسال تذكيرات استحقاق الفلاتر اليومياً في الساعة 08:00 صباحاً
//    يتحقق من التكرار تلقائياً عبر Idempotency Key
Schedule::command('filters:send-due-reminders --days=14')
    ->dailyAt('08:00')
    ->withoutOverlapping(10) // منع التداخل إذا استغرق الأمر طويلاً
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/due-reminders.log'));

// 2. تنظيف سجلات الإشعارات القديمة أسبوعياً (احتفاظ بآخر 90 يوم)
Schedule::call(function () {
    \App\Models\NotificationLog::where('created_at', '<', now()->subDays(90))
        ->whereIn('status', ['sent', 'skipped', 'failed'])
        ->delete();
})
    ->weekly()
    ->sundays()
    ->at('03:00')
    ->name('cleanup-old-notification-logs')
    ->withoutOverlapping();

