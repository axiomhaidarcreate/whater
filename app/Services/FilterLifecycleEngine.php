<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\DeviceModel;
use App\Models\Product;
use App\Models\StageReplacementHistory;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FilterLifecycleEngine
{
    /**
     * Calculate exact calendar due date handling leap years and month-end overflows.
     */
    public function calculateNextDueDate(Carbon|string $startDate, int $intervalDays, ?int $intervalMonths = null): Carbon
    {
        $date = $startDate instanceof Carbon ? $startDate->copy()->startOfDay() : Carbon::parse($startDate)->startOfDay();

        // Calculate months if not explicitly provided
        if ($intervalMonths === null) {
            if ($intervalDays % 30 === 0) {
                $intervalMonths = (int) ($intervalDays / 30);
            } elseif ($intervalDays >= 365) {
                $intervalMonths = 12 * (int) round($intervalDays / 365);
            }
        }

        if ($intervalMonths !== null && $intervalMonths > 0) {
            // Strict calendar addition without month-end overflow
            return $date->addMonthsNoOverflow($intervalMonths);
        }

        return $date->addDays($intervalDays);
    }

    /**
     * Initialize stages for a newly installed customer device from its BOM.
     */
    public function initializeDeviceStages(CustomerDevice $device, Carbon|string $installationDate): void
    {
        DB::transaction(function () use ($device, $installationDate) {
            $date = Carbon::parse($installationDate)->startOfDay();
            $model = $device->model ?? DeviceModel::with('stages.product')->findOrFail($device->device_model_id);

            foreach ($model->stages as $bomStage) {
                $intervalDays = $bomStage->default_lifespan_days;
                $intervalMonths = ($intervalDays % 30 === 0) ? (int) ($intervalDays / 30) : null;
                $nextDueAt = $this->calculateNextDueDate($date, $intervalDays, $intervalMonths);

                $stage = CustomerDeviceStage::updateOrCreate(
                    [
                        'customer_device_id' => $device->id,
                        'stage_number' => $bomStage->stage_number,
                    ],
                    [
                        'stage_name_ar' => $bomStage->stage_name_ar,
                        'product_id' => $bomStage->product_id,
                        'service_interval_days' => $intervalDays,
                        'service_interval_months' => $intervalMonths,
                        'last_actual_replacement_at' => $date->toDateString(),
                        'next_due_at' => $nextDueAt->toDateString(),
                        'status' => 'not_due',
                        'postponed_until' => null,
                        'exempted_reason' => null,
                        'exempted_by' => null,
                    ]
                );

                $this->refreshStageStatus($stage, Carbon::today());
            }

            // Update device status to active and set installation & warranty dates
            $device->update([
                'status' => 'active',
                'installation_date' => $date->toDateString(),
                'warranty_expiry_date' => $date->copy()->addYear()->toDateString(), // 1 year warranty
            ]);

            AuditLog::record(
                action: 'devices.installed_and_stages_initialized',
                model: $device,
                oldValues: ['status' => 'pending_installation'],
                newValues: ['status' => 'active', 'installation_date' => $date->toDateString()]
            );
        });
    }

    /**
     * Record actual replacement for a specific stage.
     * Crucially: leaves all other stages of the device unchanged!
     */
    public function recordStageReplacement(
        CustomerDeviceStage $stage,
        Product $newProduct,
        Carbon|string $replacementDate,
        User $technician,
        User $approver,
        ?int $ticketId = null,
        ?int $stockMovementItemId = null,
        ?string $notes = null
    ): StageReplacementHistory {
        return DB::transaction(function () use (
            $stage,
            $newProduct,
            $replacementDate,
            $technician,
            $approver,
            $ticketId,
            $stockMovementItemId,
            $notes
        ) {
            $repDate = Carbon::parse($replacementDate)->startOfDay();
            $oldProductId = $stage->product_id;

            // 1. Immutable History Log
            $history = StageReplacementHistory::create([
                'customer_device_stage_id' => $stage->id,
                'customer_device_id' => $stage->customer_device_id,
                'stage_number' => $stage->stage_number,
                'maintenance_ticket_id' => $ticketId,
                'technician_id' => $technician->id,
                'old_product_id' => $oldProductId,
                'new_product_id' => $newProduct->id,
                'replacement_date' => $repDate->toDateString(),
                'stock_movement_item_id' => $stockMovementItemId,
                'approved_by' => $approver->id,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            // 2. Recompute Next Due Date based strictly on actual replacement date
            $intervalDays = $stage->service_interval_days;
            $intervalMonths = $stage->service_interval_months;
            $newNextDueAt = $this->calculateNextDueDate($repDate, $intervalDays, $intervalMonths);

            // 3. Update ONLY this stage
            $stage->update([
                'product_id' => $newProduct->id,
                'last_actual_replacement_at' => $repDate->toDateString(),
                'next_due_at' => $newNextDueAt->toDateString(),
                'postponed_until' => null,
                'exempted_reason' => null,
                'exempted_by' => null,
                'last_ticket_id' => $ticketId,
                'technician_notes' => $notes,
            ]);

            $this->refreshStageStatus($stage, Carbon::today());

            AuditLog::record(
                action: 'stages.replaced',
                model: $stage,
                oldValues: ['old_product_id' => $oldProductId, 'previous_due' => $stage->getOriginal('next_due_at')],
                newValues: ['new_product_id' => $newProduct->id, 'next_due_at' => $newNextDueAt->toDateString()],
                user: $approver
            );

            return $history;
        });
    }

    /**
     * Postpone a stage upon customer request.
     * Does NOT alter the original last_actual_replacement_at or next_due_at.
     */
    public function postponeStage(
        CustomerDeviceStage $stage,
        Carbon|string $postponedUntil,
        string $reason,
        User $user
    ): CustomerDeviceStage {
        $postponeDate = Carbon::parse($postponedUntil)->startOfDay();

        $stage->update([
            'status' => 'postponed',
            'postponed_until' => $postponeDate->toDateString(),
            'technician_notes' => "تأجيل حتى {$postponeDate->toDateString()}: {$reason}",
        ]);

        AuditLog::record(
            action: 'stages.postponed',
            model: $stage,
            oldValues: ['status' => $stage->getOriginal('status')],
            newValues: ['status' => 'postponed', 'postponed_until' => $postponeDate->toDateString(), 'reason' => $reason],
            user: $user
        );

        return $stage;
    }

    /**
     * Exempt a stage by documented managerial decision.
     */
    public function exemptStage(
        CustomerDeviceStage $stage,
        string $reason,
        User $user
    ): CustomerDeviceStage {
        $stage->update([
            'status' => 'exempted',
            'exempted_reason' => $reason,
            'exempted_by' => $user->id,
        ]);

        AuditLog::record(
            action: 'stages.exempted',
            model: $stage,
            oldValues: ['status' => $stage->getOriginal('status')],
            newValues: ['status' => 'exempted', 'reason' => $reason, 'exempted_by' => $user->id],
            user: $user
        );

        return $stage;
    }

    /**
     * Record a technician visit without replacing any parts.
     * Crucially: DOES NOT touch any due dates or last replacement dates!
     */
    public function recordVisitWithoutReplacement(
        CustomerDevice $device,
        ?string $notes = null,
        ?User $technician = null
    ): void {
        AuditLog::record(
            action: 'devices.visited_no_replacement',
            model: $device,
            oldValues: null,
            newValues: ['notes' => $notes ?? 'زيارة فحص دون استبدال شمعات'],
            user: $technician
        );
    }

    /**
     * Re-evaluate and persist stage status relative to an evaluation date.
     */
    public function refreshStageStatus(CustomerDeviceStage $stage, ?Carbon $asOfDate = null): string
    {
        $today = ($asOfDate ?? Carbon::today())->startOfDay();
        $due = Carbon::parse($stage->next_due_at)->startOfDay();
        $reminderWindowDays = (int) SystemSetting::get('reminder_window_days', 14);

        if ($stage->status === 'exempted') {
            return 'exempted';
        }

        if ($stage->status === 'postponed' && $stage->postponed_until) {
            $postponedDate = Carbon::parse($stage->postponed_until)->startOfDay();
            if ($today->lt($postponedDate)) {
                return 'postponed';
            }
        }

        if ($today->gt($due)) {
            $newStatus = 'overdue';
        } elseif ($today->equalTo($due)) {
            $newStatus = 'due_today';
        } elseif ($today->diffInDays($due, false) <= $reminderWindowDays && $today->diffInDays($due, false) > 0) {
            $newStatus = 'upcoming';
        } else {
            $newStatus = 'not_due';
        }

        if ($stage->status !== $newStatus) {
            $stage->update(['status' => $newStatus]);
        }

        return $newStatus;
    }
}
