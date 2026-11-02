<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CustomerDevice;
use App\Models\MaintenanceTicket;
use App\Models\Product;
use App\Models\TicketAttachment;
use App\Models\TicketSparePartUsed;
use App\Services\TicketStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MaintenanceTicketController extends Controller
{
    public function __construct(
        protected TicketStateMachine $stateMachine
    ) {}

    /**
     * استعراض قائمة أوامر وتذاكر الصيانة
     * GET /api/v1/tickets
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = MaintenanceTicket::with(['customer', 'device.model', 'address', 'technician', 'supervisor']);

        // إذا كان المستخدم فنياً ميدانياً فقط، يتم تقييد الاستعلام بمهامه المسندة إليه فقط
        if ($user->hasRole('technician') && !$user->can('tickets.view')) {
            $query->where('technician_id', $user->id);
        } elseif ($request->filled('technician_id')) {
            $query->where('technician_id', $request->input('technician_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->input('service_type'));
        }

        if ($request->filled('scheduled_date')) {
            $query->whereDate('scheduled_date', $request->input('scheduled_date'));
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    /**
     * تفاصيل أمر صيانة محدد
     * GET /api/v1/tickets/{id}
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $ticket = MaintenanceTicket::with([
            'customer',
            'device.model',
            'device.stages.product',
            'address',
            'technician',
            'supervisor',
            'spareParts.product',
            'attachments.uploader',
            'interactions',
        ])->findOrFail($id);

        if ($user->hasRole('technician') && !$user->can('tickets.view') && $ticket->technician_id !== $user->id) {
            abort(403, 'غير مصرح لك باستعراض تذكرة مسندة لفني آخر.');
        }

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'data' => $ticket,
        ]);
    }

    /**
     * إسناد المهمة لفني ميداني وتحديد الموعد (المشرف)
     * POST /api/v1/tickets/{id}/assign
     */
    public function assign(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'time_slot' => 'nullable|string|max:50',
        ]);

        $ticket = MaintenanceTicket::findOrFail($id);

        // إذا كانت التذكرة مجدولة، تنتقل إلى assigned. إذا كانت مسندة بالفعل، يتم إعادة التعيين
        if ($ticket->status === 'scheduled') {
            $this->stateMachine->transition($ticket, 'assigned', $request->user(), $validated);
        } else {
            $ticket->technician_id = $validated['technician_id'];
            if (!empty($validated['scheduled_date'])) {
                $ticket->scheduled_date = $validated['scheduled_date'];
            }
            if (!empty($validated['time_slot'])) {
                $ticket->time_slot = $validated['time_slot'];
            }
            $ticket->save();
        }

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'تم إسناد المهمة للفني الميداني بنجاح.',
            'data' => $ticket->fresh(['technician']),
        ]);
    }

    /**
     * تسجيل وصول الفني للموقع (Check-in)
     * POST /api/v1/tickets/{id}/check-in
     */
    public function checkIn(int $id, Request $request): JsonResponse
    {
        $ticket = MaintenanceTicket::findOrFail($id);
        $user = $request->user();

        if ($user->hasRole('technician') && $ticket->technician_id !== $user->id) {
            abort(403, 'غير مصرح لك بتسجيل الوصول لتذكرة فني آخر.');
        }

        $this->stateMachine->transition($ticket, 'in_progress', $user);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'تم تسجيل وصول الفني للموقع بنجاح وبدء تنفيذ الصيانة.',
            'data' => [
                'ticket_id' => $ticket->id,
                'status' => $ticket->status,
                'arrived_at' => $ticket->arrived_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * رفع مرفقات وصور التذكرة (بوابات الجودة)
     * POST /api/v1/tickets/{id}/attachments
     */
    public function uploadAttachment(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:before_photo,after_photo,old_parts_photo,new_parts_photo,customer_signature,tds_reading_photo',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240', // حد أقصى 10 ميجا
        ]);

        $ticket = MaintenanceTicket::findOrFail($id);
        $user = $request->user();

        if ($user->hasRole('technician') && $ticket->technician_id !== $user->id) {
            abort(403, 'غير مصرح لك برفع مرفقات لتذكرة غير مسندة إليك.');
        }

        $file = $request->file('file');
        $path = $file->store("tickets/{$ticket->id}", 'public');

        $attachment = TicketAttachment::create([
            'maintenance_ticket_id' => $ticket->id,
            'type' => $validated['type'],
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'uploaded_by' => $user->id,
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'status_code' => 201,
            'message' => 'تم رفع وتوثيق المرفق بنجاح.',
            'data' => $attachment,
        ], 201);
    }

    /**
     * إضافة قطع غيار وشمعات مستخدمة في التذكرة
     * POST /api/v1/tickets/{id}/parts
     */
    public function addSparePart(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'stage_number' => 'nullable|integer|min:1|max:10',
            'unit_price' => 'nullable|numeric|min:0',
            'is_warranty_covered' => 'nullable|boolean',
        ]);

        $ticket = MaintenanceTicket::findOrFail($id);
        $product = Product::findOrFail($validated['product_id']);

        $price = $validated['unit_price'] ?? ($validated['is_warranty_covered'] ?? false ? 0.0000 : $product->selling_price);

        $part = TicketSparePartUsed::create([
            'maintenance_ticket_id' => $ticket->id,
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $price,
            'stage_number' => $validated['stage_number'] ?? null,
            'is_warranty_covered' => $validated['is_warranty_covered'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'status_code' => 201,
            'message' => 'تمت إضافة القطعة إلى أمر الصيانة.',
            'data' => $part->load('product'),
        ], 201);
    }

    /**
     * إنهاء الفني للمهمة وتحويلها إلى المشرف للمراجعة
     * POST /api/v1/tickets/{id}/complete
     */
    public function complete(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tds_input' => 'nullable|integer|min:0',
            'tds_output' => 'nullable|integer|min:0',
            'customer_rating' => 'nullable|integer|min:1|max:5',
            'notes' => 'nullable|string|max:1000',
            'customer_signature_path' => 'nullable|string|max:255',
        ]);

        $ticket = MaintenanceTicket::findOrFail($id);
        $user = $request->user();

        if ($user->hasRole('technician') && $ticket->technician_id !== $user->id) {
            abort(403, 'غير مصرح لك بإكمال تذكرة غير مسندة إليك.');
        }

        $this->stateMachine->transition($ticket, 'completed_pending_review', $user, $validated);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'تم إنهاء الصيانة بنجاح وإرسال التذكرة للمشرف للمراجعة والاعتماد.',
            'data' => $ticket->fresh(),
        ]);
    }

    /**
     * اعتماد المشرف وإغلاق التذكرة نهائياً
     * POST /api/v1/tickets/{id}/approve
     */
    public function approve(int $id, Request $request): JsonResponse
    {
        $ticket = MaintenanceTicket::findOrFail($id);

        $this->stateMachine->transition($ticket, 'approved_closed', $request->user());

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'تم التحقق من بوابات الجودة واعتماد إغلاق أمر الصيانة، وخصم المخزون وتحديث استحقاقات الفلاتر بنجاح.',
            'data' => $ticket->fresh(['spareParts', 'attachments']),
        ]);
    }

    /**
     * إعادة التذكرة للفني للتصحيح (Rework)
     * POST /api/v1/tickets/{id}/rework
     */
    public function rework(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rework_reason' => 'required|string|min:5|max:1000',
        ]);

        $ticket = MaintenanceTicket::findOrFail($id);

        $this->stateMachine->transition($ticket, 'returned_for_rework', $request->user(), $validated);

        return response()->json([
            'success' => true,
            'status_code' => 200,
            'message' => 'تمت إعادة أمر الصيانة للفني للتصحيح مع توثيق السبب.',
            'data' => $ticket->fresh(),
        ]);
    }
}
