<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    use ApiResponse;

    /**
     * Get public system settings (company info, working hours, currency).
     */
    public function publicSettings(): JsonResponse
    {
        $settings = SystemSetting::where('is_public', true)
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->key => SystemSetting::get($item->key)];
            });

        return $this->successResponse(
            data: $settings,
            message: 'تم استرجاع الإعدادات العامة بنجاح.'
        );
    }

    /**
     * Get all system settings (Admin only).
     */
    public function index(): JsonResponse
    {
        $settings = SystemSetting::all();

        return $this->successResponse(
            data: $settings,
            message: 'تم استرجاع كافة إعدادات النظام بنجاح.'
        );
    }

    /**
     * Update a specific system setting (Admin only).
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $request->validate([
            'value' => ['required'],
        ]);

        $setting = SystemSetting::where('key', $key)->firstOrFail();
        $oldValue = $setting->value;

        $updated = SystemSetting::set(
            key: $key,
            value: $request->value,
            group: $setting->group,
            type: $setting->type,
            descriptionAr: $setting->description_ar,
            isPublic: $setting->is_public
        );

        AuditLog::record(
            action: 'settings.update',
            model: $updated,
            oldValues: ['value' => $oldValue],
            newValues: ['value' => $request->value],
            user: $request->user()
        );

        return $this->successResponse(
            data: $updated,
            message: 'تم تحديث الإعداد بنجاح.'
        );
    }
}
