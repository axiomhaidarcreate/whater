<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Services\TelesalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallCenterController extends Controller
{
    public function __construct(
        protected TelesalesService $telesalesService
    ) {}

    /**
     * استعراض قائمة الفلاتر المستحقة والقادمة
     * GET /api/v1/call-center/due-filters
     */
    public function dueFilters(Request $request): JsonResponse
    {
        $filters = $request->only([
            'filter_status',
            'city',
            'customer_id',
            'stage_number',
            'device_model_id',
        ]);

        $perPage = (int) $request->input('per_page', 25);
        $paginated = $this->telesalesService->getDueFilters($filters, $perPage);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * تسجيل نتيجة تواصل خدمة العملاء وإنشاء تذكرة الصيانة
     * POST /api/v1/call-center/interactions
     */
    public function recordInteraction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'customer_device_id' => 'nullable|exists:customer_devices,id',
            'channel' => 'required|in:phone_call,whatsapp,in_person,system_generated',
            'outcome' => 'required|in:agreed_scheduled,postponed,no_answer,phone_off,refused_service,wrong_number,called_back',
            'notes' => 'nullable|string|max:1000',
            'follow_up_date' => 'nullable|date',
            'postponed_until' => 'nullable|date|after_or_equal:today',
            'scheduled_date' => 'nullable|date|after_or_equal:today',
            'time_slot' => 'nullable|string|max:50',
            'technician_id' => 'nullable|exists:users,id',
            'create_ticket' => 'nullable|boolean',
            'requested_stages' => 'nullable|array',
            'requested_stages.*' => 'integer|min:1|max:10',
            'service_type' => 'nullable|in:routine_filter_change,repair,installation,relocation,warranty_check',
            'priority' => 'nullable|in:low,normal,high,urgent',
        ]);

        $result = $this->telesalesService->recordInteraction($validated, $request->user());
        $interaction = $result['interaction'];
        $ticket = $result['ticket'];

        $responseData = [
            'interaction_id' => $interaction->id,
            'customer_id' => $interaction->customer_id,
            'channel' => $interaction->channel,
            'outcome' => $interaction->outcome,
            'follow_up_date' => $interaction->follow_up_date?->toDateString(),
            'notes' => $interaction->notes,
            'created_at' => $interaction->created_at->toIso8601String(),
        ];

        if ($ticket) {
            $responseData['maintenance_ticket_id'] = $ticket->id;
            $responseData['ticket_number'] = $ticket->ticket_number;
            $responseData['status'] = $ticket->status;
            $responseData['scheduled_date'] = $ticket->scheduled_date?->toDateString();
            $responseData['time_slot'] = $ticket->time_slot;
            $responseData['technician_id'] = $ticket->technician_id;
            $responseData['technician_name'] = $ticket->technician?->name;
        }

        return response()->json([
            'success' => true,
            'status_code' => 201,
            'message' => $ticket
                ? 'تم تسجيل التواصل وإنشاء أمر الصيانة بنجاح.'
                : 'تم تسجيل نتيجة التواصل مع العميل بنجاح.',
            'data' => $responseData,
        ], 201);
    }

    /**
     * استعراض ملخص أجهزة العميل وشمعاتها لموظف الكول سنتر
     * GET /api/v1/call-center/customers/{customer}/devices-summary
     */
    public function customerDevicesSummary(Customer $customer): JsonResponse
    {
        $devices = $customer->devices()
            ->with(['model', 'stages.product', 'address'])
            ->get()
            ->map(function ($device) {
                return [
                    'device_id' => $device->id,
                    'device_code' => $device->device_code,
                    'serial_number' => $device->serial_number,
                    'model_name' => $device->model?->name,
                    'status' => $device->status,
                    'installation_date' => $device->installation_date?->toDateString(),
                    'has_open_ticket' => $this->telesalesService->hasActiveTicket($device->id),
                    'stages' => $device->stages->map(function ($stage) {
                        return [
                            'stage_id' => $stage->id,
                            'stage_number' => $stage->stage_number,
                            'filter_name' => $stage->product->name_ar,
                            'service_interval_days' => $stage->service_interval_days,
                            'last_replacement' => $stage->last_actual_replacement_at?->toDateString(),
                            'next_due_at' => $stage->next_due_at?->toDateString(),
                            'status' => $stage->status,
                            'postponed_until' => $stage->postponed_until?->toDateString(),
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'data' => [
                'customer_id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->primary_phone,
                'devices' => $devices,
            ],
        ]);
    }

    /**
     * استعراض سجل تواصل خدمة العملاء
     * GET /api/v1/call-center/interactions
     */
    public function interactionsHistory(Request $request): JsonResponse
    {
        $query = CustomerInteraction::with(['customer', 'device.model', 'agent', 'ticket']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->input('outcome'));
        }

        $interactions = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'data' => $interactions->items(),
            'meta' => [
                'current_page' => $interactions->currentPage(),
                'last_page' => $interactions->lastPage(),
                'total' => $interactions->total(),
            ],
        ]);
    }
}
