<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * سجل الإشعارات المرسلة مع آلية منع التكرار عبر مفتاح التماثل الفريد (Idempotency Key).
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $idempotency_key
 * @property string $channel
 * @property string $title
 * @property string $body
 * @property array|null $data
 * @property string $status
 * @property string|null $error_message
 * @property int $attempts
 * @property \Carbon\Carbon|null $last_attempted_at
 * @property \Carbon\Carbon|null $sent_at
 */
class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'idempotency_key',
        'channel',
        'title',
        'body',
        'data',
        'status',
        'error_message',
        'attempts',
        'last_attempted_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'last_attempted_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    // ─── العلاقات (Relationships) ─────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── النطاقات (Scopes) ─────────────────────────────────

    /**
     * الإشعارات المعلقة التي لم تُرسل بعد.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * الإشعارات المرسلة بنجاح.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * الإشعارات الفاشلة.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * التحقق من وجود إشعار مكرر بنفس مفتاح التماثل.
     * يشمل جميع الحالات ما عدا 'failed' لإتاحة إعادة المحاولة عند الفشل.
     */
    public static function isDuplicate(string $idempotencyKey): bool
    {
        return self::where('idempotency_key', $idempotencyKey)
            ->whereIn('status', ['sent', 'pending', 'skipped'])
            ->exists();
    }

    /**
     * توليد مفتاح التماثل لإشعارات استحقاق الفلاتر.
     * hash(device_stage_id + due_date + channel)
     */
    public static function generateDueReminderKey(
        int $deviceStageId,
        string $dueDate,
        string $channel = 'fcm'
    ): string {
        return hash('sha256', "due_reminder:{$deviceStageId}:{$dueDate}:{$channel}");
    }

    /**
     * توليد مفتاح التماثل لإشعارات التذاكر.
     * hash(ticket_id + type)
     */
    public static function generateTicketKey(int $ticketId, string $type): string
    {
        return hash('sha256', "ticket:{$ticketId}:{$type}");
    }

    /**
     * تسجيل محاولة إرسال.
     */
    public function recordAttempt(bool $success, ?string $errorMessage = null): void
    {
        $this->increment('attempts');
        $this->update([
            'last_attempted_at' => now(),
            'status' => $success ? 'sent' : ($this->attempts >= 3 ? 'failed' : 'pending'),
            'sent_at' => $success ? now() : null,
            'error_message' => $errorMessage,
        ]);
    }
}
