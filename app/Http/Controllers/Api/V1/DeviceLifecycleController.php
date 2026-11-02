<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\Product;
use App\Services\FilterLifecycleEngine;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceLifecycleController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FilterLifecycleEngine $lifecycleEngine
    ) {}

    /**
     * Get all stages of a customer device with calculated health and due dates.
     */
    public function stages(int $deviceId): JsonResponse
    {
        $device = CustomerDevice::with(['model', 'customer', 'stages.product', 'stages.history.technician'])->findOrFail($deviceId);

        $stagesData = $device->stages->map(function ($stage) {
            $this->lifecycleEngine->refreshStageStatus($stage);

            return [
                'id' => $stage->id,
                'stage_number' => $stage->stage_number,
                'stage_name_ar' => $stage->stage_name_ar,
                'product' => [
                    'id' => $stage->product_id,
                    'sku' => $stage->product->sku,
                    'name_ar' => $stage->product->name_ar,
                ],
                'service_interval_days' => $stage->service_interval_days,
                'service_interval_months' => $stage->service_interval_months,
                'last_actual_replacement_at' => $stage->last_actual_replacement_at->toDateString(),
                'next_due_at' => $stage->next_due_at->toDateString(),
                'status' => $stage->status,
                'days_remaining' => $stage->days_remaining,
                'health_percentage' => $stage->health_percentage,
                'postponed_until' => $stage->postponed_until?->toDateString(),
                'technician_notes' => $stage->technician_notes,
                'replacement_history' => $stage->history->map(function ($h) {
                    return [
                        'id' => $h->id,
                        'replacement_date' => $h->replacement_date->toDateString(),
                        'technician_name' => $h->technician->name,
                        'notes' => $h->notes,
                    ];
                }),
            ];
        });

        return $this->successResponse(
            data: [
                'device' => [
                    'id' => $device->id,
                    'device_code' => $device->device_code,
                    'model_name' => $device->model->name,
                    'customer_name' => $device->customer->name,
                    'status' => $device->status,
                    'installation_date' => $device->installation_date?->toDateString(),
                ],
                'stages' => $stagesData,
            ],
            message: 'تم استرجاع مراحل وشمعات الجهاز بنجاح.'
        );
    }

    /**
     * Confirm device installation and initialize filter stages.
     */
    public function install(Request $request, int $deviceId): JsonResponse
    {
        $validated = $request->validate([
            'installation_date' => ['required', 'date'],
        ]);

        $device = CustomerDevice::findOrFail($deviceId);

        $this->lifecycleEngine->initializeDeviceStages(
            device: $device,
            installationDate: $validated['installation_date']
        );

        return $this->successResponse(
            data: $device->fresh(['stages.product']),
            message: 'تم تأكيد تركيب الجهاز وتهيئة مواعيد استحقاق الشمعات بنجاح.'
        );
    }

    /**
     * Record actual replacement for a stage.
     */
    public function replaceStage(Request $request, int $stageId): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'replacement_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $stage = CustomerDeviceStage::findOrFail($stageId);
        $newProduct = Product::findOrFail($validated['product_id']);

        $history = $this->lifecycleEngine->recordStageReplacement(
            stage: $stage,
            newProduct: $newProduct,
            replacementDate: $validated['replacement_date'],
            technician: $request->user(),
            approver: $request->user(),
            notes: $validated['notes'] ?? null
        );

        return $this->successResponse(
            data: [
                'stage' => $stage->fresh(['product']),
                'history' => $history,
            ],
            message: 'تم تسجيل استبدال الشمعة وإعادة احتساب الموعد القادم بنجاح.'
        );
    }

    /**
     * Postpone a stage upon customer request.
     */
    public function postpone(Request $request, int $stageId): JsonResponse
    {
        $validated = $request->validate([
            'postponed_until' => ['required', 'date', 'after:today'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $stage = CustomerDeviceStage::findOrFail($stageId);

        $this->lifecycleEngine->postponeStage(
            stage: $stage,
            postponedUntil: $validated['postponed_until'],
            reason: $validated['reason'],
            user: $request->user()
        );

        return $this->successResponse(
            data: $stage->fresh(),
            message: 'تم تأجيل موعد استحقاق الشمعة مع الحفاظ على سجل الاستحقاق الأصلي.'
        );
    }

    /**
     * Exempt a stage by administrative decision.
     */
    public function exempt(Request $request, int $stageId): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $stage = CustomerDeviceStage::findOrFail($stageId);

        $this->lifecycleEngine->exemptStage(
            stage: $stage,
            reason: $validated['reason'],
            user: $request->user()
        );

        return $this->successResponse(
            data: $stage->fresh(),
            message: 'تم استثناء الشمعة بقرار إداري موثق.'
        );
    }
}
