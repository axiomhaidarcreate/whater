<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DeviceModel;
use App\Models\DeviceModelStage;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceModelController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of device models with BOM stages.
     */
    public function index(): JsonResponse
    {
        $models = DeviceModel::with(['stages.product'])->get();

        return $this->successResponse(
            data: $models,
            message: 'تم استرجاع قائمة موديلات الأجهزة بنجاح.'
        );
    }

    /**
     * Display a single device model with full BOM.
     */
    public function show(int $id): JsonResponse
    {
        $model = DeviceModel::with(['stages.product'])->findOrFail($id);

        return $this->successResponse(
            data: $model,
            message: 'تم استرجاع تفاصيل الموديل بنجاح.'
        );
    }

    /**
     * Store a newly created device model along with its BOM stages.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_code' => ['required', 'string', 'max:64', 'unique:device_models,model_code'],
            'name' => ['required', 'string', 'max:191'],
            'stages_count' => ['required', 'integer', 'min:1', 'max:12'],
            'description' => ['nullable', 'string'],
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.stage_number' => ['required', 'integer', 'min:1'],
            'stages.*.stage_name_ar' => ['required', 'string', 'max:100'],
            'stages.*.product_id' => ['required', 'exists:products,id'],
            'stages.*.default_lifespan_days' => ['required', 'integer', 'min:1'],
        ]);

        $deviceModel = DB::transaction(function () use ($validated, $request) {
            $model = DeviceModel::create([
                'model_code' => $validated['model_code'],
                'name' => $validated['name'],
                'stages_count' => $validated['stages_count'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            foreach ($validated['stages'] as $stageData) {
                DeviceModelStage::create([
                    'device_model_id' => $model->id,
                    'stage_number' => $stageData['stage_number'],
                    'stage_name_ar' => $stageData['stage_name_ar'],
                    'product_id' => $stageData['product_id'],
                    'default_lifespan_days' => $stageData['default_lifespan_days'],
                    'is_mandatory' => true,
                ]);
            }

            AuditLog::record('catalog.device_model_created', $model, null, $validated, $request->user());

            return $model->load('stages.product');
        });

        return $this->successResponse(
            data: $deviceModel,
            message: 'تم تعريف موديل الجهاز وقائمة مكوناته (BOM) بنجاح.',
            statusCode: 201
        );
    }
}
