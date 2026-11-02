<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\MaintenanceTicket;
use App\Models\TicketSparePartUsed;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TicketStateMachine
{
    /**
     * جدول الانتقالات النظامية الصارمة لحالات تذاكر الصيانة
     */
    public const ALLOWED_TRANSITIONS = [
        'new' => ['scheduled', 'cancelled'],
        'under_review' => ['scheduled', 'cancelled'],
        'waiting_customer_confirmation' => ['scheduled', 'cancelled'],
        'scheduled' => ['assigned', 'cancelled', 'postponed'],
        'assigned' => ['in_progress', 'unreachable', 'postponed', 'cancelled', 'scheduled'],
        'in_progress' => ['waiting_parts', 'completed_pending_review', 'postponed', 'cancelled'],
        'waiting_parts' => ['in_progress', 'postponed', 'cancelled'],
        'completed_pending_review' => ['approved_closed', 'returned_for_rework'],
        'returned_for_rework' => ['in_progress', 'cancelled'],
        'unreachable' => ['scheduled', 'cancelled'],
        'postponed' => ['scheduled', 'cancelled'],
        'approved_closed' => [], // حالة نهائية
        'cancelled' => [], // حالة نهائية
    ];

    public function __construct(
        protected InventoryService $inventoryService,
        protected FilterLifecycleEngine $lifecycleEngine,
        protected NotificationService $notificationService
    ) {}

    /**
     * إرسال إشعارات FCM بعد إتمام الانتقال بنجاح (خارج الـ DB transaction)
     */
    private function dispatchPostTransitionNotifications(
        MaintenanceTicket $ticket,
        string $newStatus,
        array $context
    ): void {
        try {
            $customerName = $ticket->customer?->name ?? 'العميل';

            match ($newStatus) {
                // إسناد مهمة للفني
                'assigned' => $ticket->technician
                    ? $this->notificationService->notifyTicketAssigned(
                        ticketId: $ticket->id,
                        technician: $ticket->technician,
                        customerName: $customerName
                    )
                    : null,

                // إعادة التذكرة للتصحيح
                'returned_for_rework' => $ticket->technician
                    ? $this->notificationService->notifyTicketRework(
                        ticketId: $ticket->id,
                        technician: $ticket->technician,
                        reason: $context['rework_reason'] ?? 'راجع ملاحظات المشرف'
                    )
                    : null,

                // اعتماد وإغلاق التذكرة
                'approved_closed' => $ticket->technician
                    ? $this->notificationService->notifyTicketApproved(
                        ticketId: $ticket->id,
                        technician: $ticket->technician
                    )
                    : null,

                default => null,
            };
        } catch (\Throwable $e) {
            // لا نرفع استثناء بسبب فشل الإشعار — العملية الأساسية اكتملت بنجاح
            Log::error('TicketStateMachine: فشل إرسال إشعار FCM: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'new_status' => $newStatus,
            ]);
        }
    }


    /**
     * فحص هل الانتقال مسموح من الحالة الحالية إلى الحالة المستهدفة
     */
    public function canTransition(MaintenanceTicket $ticket, string $newStatus): bool
    {
        $current = $ticket->status;
        $allowed = self::ALLOWED_TRANSITIONS[$current] ?? [];
        return in_array($newStatus, $allowed, true);
    }

    /**
     * بوابات التحقق الإلزامية قبل الإغلاق والاعتماد من المشرف (Mandatory QA Gates)
     */
    public function validateQAGates(MaintenanceTicket $ticket): void
    {
        $attachments = $ticket->attachments()->pluck('type')->toArray();

        // 1. صورة فحص ما قبل العمل (Before Photo)
        if (!in_array('before_photo', $attachments, true)) {
            throw ValidationException::withMessages([
                'qa_gate' => 'لا يمكن اعتماد التذكرة: صورة الفحص المسبق (before_photo) إلزامية ولم يتم رفعها من قبل الفني.'
            ]);
        }

        // 2. صورة العمل المنجز (After Photo)
        if (!in_array('after_photo', $attachments, true)) {
            throw ValidationException::withMessages([
                'qa_gate' => 'لا يمكن اعتماد التذكرة: صورة إنجاز العمل بعد الصيانة (after_photo) إلزامية.'
            ]);
        }

        // 3. في حال تغيير شمعات أو قطع: صورة القطع القديمة أو الجديدة
        if ($ticket->spareParts()->exists()) {
            $hasPartsPhoto = in_array('old_parts_photo', $attachments, true)
                || in_array('new_parts_photo', $attachments, true);

            if (!$hasPartsPhoto) {
                throw ValidationException::withMessages([
                    'qa_gate' => 'لا يمكن اعتماد التذكرة: تم استخدام قطع غيار/شمعات، ويجب رفع صورة توثق القطع (old_parts_photo أو new_parts_photo).'
                ]);
            }
        }

        // 4. قراءات الأملاح TDS في حال الصيانة الدورية واستبدال الفلاتر
        if ($ticket->service_type === 'routine_filter_change') {
            if ($ticket->tds_input === null || $ticket->tds_output === null) {
                throw ValidationException::withMessages([
                    'qa_gate' => 'لا يمكن اعتماد التذكرة: يجب تسجيل قراءة نسبة الأملاح قبل وبعد الفلترة (TDS In / TDS Out).'
                ]);
            }

            if ($ticket->tds_output > $ticket->tds_input) {
                throw ValidationException::withMessages([
                    'qa_gate' => 'خطأ في قراءة TDS: نسبة أملاح ما بعد الفلترة أعلى من ما قبل الفلترة! يجب التحقق من توصيلات الشمعات أو صحة القراءة.'
                ]);
            }
        }
    }

    /**
     * تنفيذ انتقال حالة التذكرة مع تطبيق الصلاحيات والآثار الجانبية المخزنية والتشغيلية
     */
    public function transition(
        MaintenanceTicket $ticket,
        string $newStatus,
        User $user,
        array $context = []
    ): MaintenanceTicket {
        if (!$this->canTransition($ticket, $newStatus)) {
            throw ValidationException::withMessages([
                'status' => "لا يمكن الانتقال من حالة [{$ticket->status}] إلى [{$newStatus}] وفقاً لمسار حالات الصيانة المعتمد."
            ]);
        }

        return DB::transaction(function () use ($ticket, $newStatus, $user, $context) {
            $oldStatus = $ticket->status;

            switch ($newStatus) {
                case 'assigned':
                    $this->handleAssignedTransition($ticket, $user, $context);
                    break;

                case 'in_progress':
                    $this->handleInProgressTransition($ticket, $user);
                    break;

                case 'completed_pending_review':
                    $this->handleCompletedPendingReviewTransition($ticket, $user, $context);
                    break;

                case 'returned_for_rework':
                    $this->handleReturnedForReworkTransition($ticket, $user, $context);
                    break;

                case 'approved_closed':
                    $this->handleApprovedClosedTransition($ticket, $user, $context);
                    break;

                case 'cancelled':
                    $ticket->notes = ($ticket->notes ? $ticket->notes . "\n" : '') .
                        ('تم الإلغاء بواسطة ' . $user->name . ': ' . ($context['cancellation_reason'] ?? ''));
                    break;

                case 'unreachable':
                    $ticket->notes = ($ticket->notes ? $ticket->notes . "\n" : '') .
                        ('تعذر الوصول للعميل في زيارة ' . now()->toDateString());
                    break;

                case 'postponed':
                    if (!empty($context['postponed_until'])) {
                        $ticket->scheduled_date = Carbon::parse($context['postponed_until'])->toDateString();
                    }
                    break;
            }

            $ticket->status = $newStatus;
            $ticket->save();

            AuditLog::record(
                action: 'tickets.status_changed',
                model: $ticket,
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $newStatus],
                user: $user
            );

            return $ticket;
        });

        // إرسال إشعارات FCM بعد إتمام الـ Transaction (خارج الـ transaction لضمان الإرسال)
        $this->dispatchPostTransitionNotifications($ticket, $newStatus, $context);
    }

    /**
     * إسناد المهمة لفني ميداني
     */
    protected function handleAssignedTransition(MaintenanceTicket $ticket, User $user, array $context): void
    {
        if (empty($context['technician_id'])) {
            throw ValidationException::withMessages([
                'technician_id' => 'يجب تحديد الفني الميداني لإسناد أمر الصيانة.'
            ]);
        }

        $technician = User::where('id', $context['technician_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $ticket->technician_id = $technician->id;
        $ticket->supervisor_id = $user->id;

        if (!empty($context['scheduled_date'])) {
            $ticket->scheduled_date = $context['scheduled_date'];
        }
        if (!empty($context['time_slot'])) {
            $ticket->time_slot = $context['time_slot'];
        }
    }

    /**
     * تسجيل وصول الفني للموقع (Check-in)
     */
    protected function handleInProgressTransition(MaintenanceTicket $ticket, User $user): void
    {
        if (!$ticket->arrived_at) {
            $ticket->arrived_at = now();
        }
    }

    /**
     * إنهاء الفني للعمل ورفع القراءات وتحويل التذكرة لمراجعة المشرف
     */
    protected function handleCompletedPendingReviewTransition(MaintenanceTicket $ticket, User $user, array $context): void
    {
        $ticket->completed_at = now();

        if (isset($context['tds_input'])) {
            $ticket->tds_input = (int) $context['tds_input'];
        }
        if (isset($context['tds_output'])) {
            $ticket->tds_output = (int) $context['tds_output'];
        }
        if (!empty($context['customer_signature_path'])) {
            $ticket->customer_signature_path = $context['customer_signature_path'];
        }
        if (!empty($context['customer_rating'])) {
            $ticket->customer_rating = (int) $context['customer_rating'];
        }
        if (!empty($context['notes'])) {
            $ticket->notes = ($ticket->notes ? $ticket->notes . "\n" : '') . $context['notes'];
        }
    }

    /**
     * إعادة التذكرة للتصحيح من المشرف (Rework)
     */
    protected function handleReturnedForReworkTransition(MaintenanceTicket $ticket, User $user, array $context): void
    {
        if (empty($context['rework_reason'])) {
            throw ValidationException::withMessages([
                'rework_reason' => 'يجب توضيح سبب إعادة أمر الصيانة للفني للتصحيح (Rework Reason).'
            ]);
        }

        $ticket->rework_reason = $context['rework_reason'];
        $ticket->supervisor_id = $user->id;
    }

    /**
     * اعتماد المشرف وإغلاق التذكرة نهائياً
     */
    protected function handleApprovedClosedTransition(MaintenanceTicket $ticket, User $user, array $context): void
    {
        // 1. التحقق الصارم من بوابات الجودة (QA Gates)
        $this->validateQAGates($ticket);

        $ticket->supervisor_id = $user->id;

        // 2. خصم قطع الغيار والشمعات من مستودع سيارة الفني الميداني
        $technician = $ticket->technician;
        $vanWarehouse = null;

        if ($technician) {
            $vanWarehouse = Warehouse::where('type', 'technician_van')
                ->where('assigned_user_id', $technician->id)
                ->where('is_active', true)
                ->first();
        }

        $spareParts = $ticket->spareParts()->with('product')->get();
        $totalCost = 0;

        foreach ($spareParts as $part) {
            $qty = $part->quantity;
            $unitPrice = $part->unit_price;
            $totalCost += ($qty * $unitPrice);

            // خصم المخزون من سيارة الفني إن وُجد المستودع
            if ($vanWarehouse) {
                $this->inventoryService->decreaseStock(
                    warehouse: $vanWarehouse,
                    product: $part->product,
                    quantity: $qty,
                    movementType: 'ticket_consumption',
                    reference: $ticket,
                    notes: "صرف قطع غيار لأمر الصيانة {$ticket->ticket_number}",
                    user: $user
                );
            }

            // 3. تحديث دورة حياة الشمعة في محرك الفلاتر (Filter Lifecycle Engine)
            if ($part->stage_number && $ticket->customer_device_id) {
                $stage = CustomerDeviceStage::where('customer_device_id', $ticket->customer_device_id)
                    ->where('stage_number', $part->stage_number)
                    ->first();

                if ($stage) {
                    $this->lifecycleEngine->recordStageReplacement(
                        stage: $stage,
                        newProduct: $part->product,
                        replacementDate: $ticket->completed_at ?? now(),
                        technician: $technician ?? $user,
                        approver: $user,
                        ticketId: $ticket->id,
                        notes: "تم الاستبدال عبر تذكرة الصيانة {$ticket->ticket_number}"
                    );
                }
            }
        }

        // 4. في حال كانت التذكرة زيارة تركيب جهاز جديد (installation)
        if ($ticket->service_type === 'installation') {
            $device = CustomerDevice::find($ticket->customer_device_id);
            if ($device && $device->status === 'pending_installation') {
                $installDate = $ticket->completed_at ? Carbon::parse($ticket->completed_at) : Carbon::today();
                $this->lifecycleEngine->initializeDeviceStages($device, $installDate);
            }
        }

        $ticket->total_cost = $totalCost;
    }
}
