<?php

namespace Tests\Feature;

use App\Jobs\SendFcmNotificationJob;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\MaintenanceTicket;
use App\Models\NotificationLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * اختبارات شاملة للمرحلة 09: الإشعارات الفورية ومنع التكرار والمهام المجدولة
 */
class NotificationsAndSchedulerTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notificationService = app(NotificationService::class);
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // التأكد من أن FCM معطل في الاختبارات (محاكاة)
        config(['services.fcm.enabled' => false]);
    }

    /**
     * جلب المستخدم الأول بالدور المطلوب.
     * Role model تستخدم حقل `name` كـ slug.
     */
    private function getUserByRole(string $roleName): ?User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', $roleName))->first();
    }

    // ─────────────────────────────────────────────────────────────────
    // 01. اختبارات تسجيل توكنات FCM
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function technician_can_register_fcm_token(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician, 'يجب أن يوجد مستخدم بدور technician في البيانات التجريبية');

        $response = $this->actingAs($technician, 'sanctum')
            ->postJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'test_fcm_token_abc123',
                'device_uuid' => 'device-uuid-001',
                'platform' => 'android',
            ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.device_uuid', 'device-uuid-001')
                 ->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $technician->id,
            'device_uuid' => 'device-uuid-001',
            'platform' => 'android',
        ]);
    }

    /** @test */
    public function registering_same_device_uuid_updates_existing_token(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        // أول تسجيل
        $this->actingAs($technician, 'sanctum')
            ->postJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'old_token',
                'device_uuid' => 'device-uuid-002',
                'platform' => 'android',
            ]);

        // تحديث نفس الجهاز بتوكن جديد
        $this->actingAs($technician, 'sanctum')
            ->postJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'new_token_updated',
                'device_uuid' => 'device-uuid-002',
                'platform' => 'android',
            ]);

        // يجب أن يكون هناك سجل واحد فقط
        $this->assertEquals(1, UserDeviceToken::where('user_id', $technician->id)
            ->where('device_uuid', 'device-uuid-002')
            ->count());

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $technician->id,
            'device_uuid' => 'device-uuid-002',
            'fcm_token' => 'new_token_updated',
        ]);
    }

    /** @test */
    public function fcm_token_registration_requires_all_fields(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        $this->actingAs($technician, 'sanctum')
            ->postJson('/api/v1/auth/fcm-token', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fcm_token', 'device_uuid', 'platform']);
    }

    /** @test */
    public function unauthenticated_user_cannot_register_fcm_token(): void
    {
        $this->postJson('/api/v1/auth/fcm-token', [
            'fcm_token' => 'test',
            'device_uuid' => 'test-uuid',
            'platform' => 'android',
        ])->assertStatus(401);
    }

    // ─────────────────────────────────────────────────────────────────
    // 02. اختبارات منع تكرار الإشعارات (Idempotency)
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function notification_is_sent_only_once_for_same_idempotency_key(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        $idempotencyKey = 'test_unique_key_12345';

        // الإرسال الأول
        $log1 = $this->notificationService->sendToUser(
            user: $technician,
            type: 'test_notification',
            title: 'اختبار',
            body: 'رسالة تجريبية',
            data: [],
            idempotencyKey: $idempotencyKey
        );

        // محاولة الإرسال الثانية بنفس المفتاح
        $log2 = $this->notificationService->sendToUser(
            user: $technician,
            type: 'test_notification',
            title: 'اختبار',
            body: 'رسالة تجريبية',
            data: [],
            idempotencyKey: $idempotencyKey
        );

        // يجب أن تُرجع null للإشعار المكرر
        $this->assertNull($log2, 'يجب تخطي الإشعار المكرر وإرجاع null');

        // يجب أن يكون هناك سجل واحد فقط
        $this->assertEquals(1, NotificationLog::where('idempotency_key', $idempotencyKey)->count());
    }

    /** @test */
    public function different_idempotency_keys_allow_multiple_notifications(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        $this->notificationService->sendToUser(
            user: $technician,
            type: 'test',
            title: 'أول',
            body: 'أول',
            idempotencyKey: 'key_a'
        );

        $this->notificationService->sendToUser(
            user: $technician,
            type: 'test',
            title: 'ثاني',
            body: 'ثاني',
            idempotencyKey: 'key_b'
        );

        $this->assertEquals(2, NotificationLog::where('user_id', $technician->id)->count());
    }

    /** @test */
    public function notification_is_skipped_when_user_has_no_active_devices(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        // التأكد من عدم وجود توكنات مسجلة
        UserDeviceToken::where('user_id', $technician->id)->delete();

        $log = $this->notificationService->sendToUser(
            user: $technician,
            type: 'test',
            title: 'اختبار',
            body: 'بدون أجهزة',
            idempotencyKey: 'no_device_key'
        );

        $this->assertNotNull($log);
        $this->assertEquals('skipped', $log->status);
    }

    // ─────────────────────────────────────────────────────────────────
    // 03. اختبارات توليد مفاتيح التماثل (Idempotency Keys)
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function due_reminder_idempotency_key_is_deterministic(): void
    {
        $key1 = NotificationLog::generateDueReminderKey(42, '2027-03-15', 'fcm');
        $key2 = NotificationLog::generateDueReminderKey(42, '2027-03-15', 'fcm');
        $keyDifferent = NotificationLog::generateDueReminderKey(42, '2027-03-16', 'fcm');

        $this->assertEquals($key1, $key2, 'يجب أن ينتج نفس المفتاح لنفس المدخلات');
        $this->assertNotEquals($key1, $keyDifferent, 'يجب أن ينتج مفتاحاً مختلفاً لتاريخ مختلف');
    }

    /** @test */
    public function ticket_idempotency_key_is_deterministic(): void
    {
        $key1 = NotificationLog::generateTicketKey(100, 'ticket_assigned');
        $key2 = NotificationLog::generateTicketKey(100, 'ticket_assigned');
        $keyDifferentType = NotificationLog::generateTicketKey(100, 'ticket_rework');

        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $keyDifferentType);
    }

    // ─────────────────────────────────────────────────────────────────
    // 04. اختبارات SendFcmNotificationJob
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function send_fcm_notification_job_can_be_dispatched(): void
    {
        Queue::fake();

        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        SendFcmNotificationJob::dispatch(
            userId: $technician->id,
            type: 'ticket_assigned',
            title: 'مهمة جديدة',
            body: 'تم تعيين مهمة جديدة لك',
            data: ['ticket_id' => 1],
            idempotencyKey: 'test_job_key'
        );

        Queue::assertPushed(SendFcmNotificationJob::class, function ($job) use ($technician) {
            return $job->userId === $technician->id
                && $job->type === 'ticket_assigned'
                && $job->idempotencyKey === 'test_job_key';
        });
    }

    /** @test */
    public function send_fcm_notification_job_skips_nonexistent_user(): void
    {
        $job = new SendFcmNotificationJob(
            userId: 99999,
            type: 'test',
            title: 'Test',
            body: 'Test',
        );

        // يجب أن لا يرفع استثناء بل يتخطى بهدوء
        $job->handle(app(NotificationService::class));

        $this->assertEquals(0, NotificationLog::count());
    }

    /** @test */
    public function send_fcm_notification_job_skips_duplicate_idempotency_key(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        $existingKey = 'already_sent_key_999';

        // إنشاء سجل إشعار موجود مسبقاً بنفس المفتاح بحالة 'sent'
        NotificationLog::create([
            'user_id' => $technician->id,
            'type' => 'ticket_assigned',
            'idempotency_key' => $existingKey,
            'channel' => 'fcm',
            'title' => 'سابق',
            'body' => 'سابق',
            'status' => 'sent',
        ]);

        $job = new SendFcmNotificationJob(
            userId: $technician->id,
            type: 'ticket_assigned',
            title: 'جديد',
            body: 'جديد',
            data: [],
            idempotencyKey: $existingKey
        );

        $job->handle(app(NotificationService::class));

        // يجب أن يبقى عدد السجلات 1 (لم يُضف سجل جديد)
        $this->assertEquals(1, NotificationLog::where('idempotency_key', $existingKey)->count());
    }

    // ─────────────────────────────────────────────────────────────────
    // 05. اختبارات أمر الاستحقاق اليومي (filters:send-due-reminders)
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function due_reminders_command_runs_successfully(): void
    {
        $this->artisan('filters:send-due-reminders --dry-run')
             ->assertSuccessful();
    }

    /** @test */
    public function due_reminders_command_queues_jobs_for_due_stages(): void
    {
        Queue::fake();

        // البحث عن جهاز نشط له مراحل
        $device = CustomerDevice::where('status', 'active')
            ->whereHas('stages')
            ->with('customer.user', 'stages')
            ->first();

        if (! $device || ! $device->customer?->user) {
            $this->markTestSkipped('لا توجد أجهزة نشطة بمستخدم مرتبط في بيانات الاختبار.');
        }

        $stage = $device->stages->first();
        $stage->update([
            'next_due_at' => now()->toDateString(),
            'status' => 'due_today',
            'postponed_until' => null,
        ]);

        // تسجيل توكن FCM للعميل
        UserDeviceToken::updateOrCreate(
            ['user_id' => $device->customer->user->id, 'device_uuid' => 'cust-device-001'],
            ['fcm_token' => 'customer_test_token', 'platform' => 'android', 'last_seen_at' => now()]
        );

        // التأكد من عدم وجود تذاكر مفتوحة لهذا الجهاز
        MaintenanceTicket::where('customer_device_id', $device->id)
            ->whereIn('status', ['new', 'scheduled', 'assigned', 'in_progress', 'waiting_parts'])
            ->update(['status' => 'cancelled']);

        $this->artisan('filters:send-due-reminders --days=1')
             ->assertSuccessful();

        Queue::assertPushed(SendFcmNotificationJob::class);
    }

    /** @test */
    public function due_reminders_command_skips_stages_with_open_tickets(): void
    {
        Queue::fake();

        $device = CustomerDevice::where('status', 'active')->whereHas('stages')->first();

        if (! $device) {
            $this->markTestSkipped('لا توجد أجهزة نشطة في بيانات الاختبار.');
        }

        $stage = $device->stages()->first();
        if (! $stage) {
            $this->markTestSkipped('لا توجد مراحل للجهاز.');
        }

        $stage->update([
            'next_due_at' => now()->toDateString(),
            'status' => 'due_today',
            'postponed_until' => null,
        ]);

        // إنشاء تذكرة مفتوحة لهذا الجهاز
        $supervisor = $this->getUserByRole('maintenance_supervisor');
        if ($supervisor) {
            MaintenanceTicket::where('customer_device_id', $device->id)->delete();
            MaintenanceTicket::create([
                'ticket_number' => 'TKT-TEST-SKIP-01',
                'customer_id' => $device->customer_id,
                'customer_device_id' => $device->id,
                'service_type' => 'filter_maintenance',
                'status' => 'new',
                'created_by' => $supervisor->id,
            ]);
        }

        $this->artisan('filters:send-due-reminders --days=1')
             ->assertSuccessful();

        // يجب ألا يُرسل إشعار للجهاز الذي له تذكرة مفتوحة
        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }

    /** @test */
    public function due_reminders_command_skips_postponed_stages(): void
    {
        Queue::fake();

        $device = CustomerDevice::where('status', 'active')->whereHas('stages')->first();

        if (! $device) {
            $this->markTestSkipped('لا توجد أجهزة نشطة.');
        }

        $stage = $device->stages()->first();
        if (! $stage) {
            $this->markTestSkipped('لا توجد مراحل للجهاز.');
        }

        // إلغاء جميع التذاكر المفتوحة لهذا الجهاز
        MaintenanceTicket::where('customer_device_id', $device->id)
            ->whereIn('status', ['new', 'scheduled', 'assigned', 'in_progress', 'waiting_parts'])
            ->update(['status' => 'cancelled']);

        $stage->update([
            'next_due_at' => now()->toDateString(),
            'status' => 'upcoming',
            'postponed_until' => now()->addWeek()->toDateString(),
        ]);

        $this->artisan('filters:send-due-reminders --days=1')
             ->assertSuccessful();

        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }

    /** @test */
    public function due_reminders_command_does_not_send_duplicate_reminders(): void
    {
        Queue::fake();

        $device = CustomerDevice::where('status', 'active')->whereHas('stages')->first();

        if (! $device) {
            $this->markTestSkipped('لا توجد أجهزة نشطة.');
        }

        $stage = $device->stages()->first();
        if (! $stage) {
            $this->markTestSkipped('لا توجد مراحل للجهاز.');
        }

        $dueDate = now()->toDateString();
        $stage->update([
            'next_due_at' => $dueDate,
            'status' => 'due_today',
            'postponed_until' => null,
        ]);

        // إلغاء التذاكر المفتوحة
        MaintenanceTicket::where('customer_device_id', $device->id)
            ->whereIn('status', ['new', 'scheduled', 'assigned', 'in_progress', 'waiting_parts'])
            ->update(['status' => 'cancelled']);

        // إنشاء إشعار مسبق بنفس مفتاح التماثل
        $idempotencyKey = NotificationLog::generateDueReminderKey($stage->id, $dueDate, 'fcm');
        NotificationLog::create([
            'user_id' => User::first()->id,
            'type' => 'due_reminder',
            'idempotency_key' => $idempotencyKey,
            'channel' => 'fcm',
            'title' => 'تذكير سابق',
            'body' => 'تذكير سابق',
            'status' => 'sent',
        ]);

        $this->artisan('filters:send-due-reminders --days=1')
             ->assertSuccessful();

        // يجب ألا يُضاف Job جديد للطابور
        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }

    // ─────────────────────────────────────────────────────────────────
    // 06. اختبارات تكامل الإشعارات مع TicketStateMachine
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function assigning_ticket_does_not_throw_when_notification_fails(): void
    {
        $supervisor = $this->getUserByRole('maintenance_supervisor');
        $technician = $this->getUserByRole('technician');

        $ticket = MaintenanceTicket::where('status', 'new')->first();

        if (! $ticket || ! $supervisor || ! $technician) {
            $this->markTestSkipped('بيانات الاختبار غير متوفرة.');
        }

        // الإسناد يجب أن ينجح حتى لو فشل الإشعار (FCM معطل في الاختبارات)
        $response = $this->actingAs($supervisor, 'sanctum')
            ->postJson("/api/v1/tickets/{$ticket->id}/assign", [
                'technician_id' => $technician->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'morning',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // 07. اختبارات NotificationLog Model
    // ─────────────────────────────────────────────────────────────────

    /** @test */
    public function notification_log_is_duplicate_check_works_correctly(): void
    {
        $user = User::first();

        NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'test',
            'idempotency_key' => 'unique_check_key',
            'channel' => 'fcm',
            'title' => 'Test',
            'body' => 'Test',
            'status' => 'sent',
        ]);

        $this->assertTrue(NotificationLog::isDuplicate('unique_check_key'));
        $this->assertFalse(NotificationLog::isDuplicate('non_existent_key'));
    }

    /** @test */
    public function notification_log_record_attempt_updates_status_correctly(): void
    {
        $user = User::first();

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'test',
            'idempotency_key' => 'attempt_test_key',
            'channel' => 'fcm',
            'title' => 'Test',
            'body' => 'Test',
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // محاولة ناجحة
        $log->recordAttempt(true);
        $log->refresh();

        $this->assertEquals('sent', $log->status);
        $this->assertEquals(1, $log->attempts);
        $this->assertNotNull($log->sent_at);
    }

    /** @test */
    public function notification_log_failed_status_after_3_failed_attempts(): void
    {
        $user = User::first();

        $log = NotificationLog::create([
            'user_id' => $user->id,
            'type' => 'test',
            'idempotency_key' => 'fail_attempt_key',
            'channel' => 'fcm',
            'title' => 'Test',
            'body' => 'Test',
            'status' => 'pending',
            'attempts' => 2, // محاولتان سابقتان
        ]);

        // المحاولة الثالثة والأخيرة تفشل
        $log->recordAttempt(false, 'FCM server error');
        $log->refresh();

        $this->assertEquals('failed', $log->status);
        $this->assertEquals(3, $log->attempts);
        $this->assertEquals('FCM server error', $log->error_message);
    }

    /** @test */
    public function notification_service_notify_ticket_assigned_creates_log(): void
    {
        $technician = $this->getUserByRole('technician');
        $this->assertNotNull($technician);

        // تسجيل توكن للفني
        UserDeviceToken::updateOrCreate(
            ['user_id' => $technician->id, 'device_uuid' => 'tech-device-001'],
            ['fcm_token' => 'tech_token_abc', 'platform' => 'android', 'last_seen_at' => now()]
        );

        $log = $this->notificationService->notifyTicketAssigned(
            ticketId: 100,
            technician: $technician,
            customerName: 'العميل التجريبي'
        );

        $this->assertNotNull($log);
        $this->assertEquals('ticket_assigned', $log->type);
        $this->assertEquals($technician->id, $log->user_id);
        $this->assertContains($log->status, ['sent', 'skipped']);
    }
}
