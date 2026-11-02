<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\CustomerInteraction;
use App\Models\MaintenanceTicket;
use App\Models\TicketSparePartUsed;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TelesalesService
{
    public function __construct(
        protected FilterLifecycleEngine $lifecycleEngine
    ) {}

    /**
     * جلب الفلاتر المستحقة والقادمة مع دعم التصفية والفرز بحسب الأولوية
     */
    public function getDueFilters(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $today = Carbon::today();

        $query = CustomerDeviceStage::query()
            ->with([
                'device.customer',
                'device.address',
                'device.model',
                'product'
            ])
            ->whereHas('device', function ($q) {
                $q->where('status', 'active');
            });

        // تصفية حسب حالة الاستحقاق
        if (!empty($filters['filter_status'])) {
            $query->where('status', $filters['filter_status']);
        } else {
            $query->whereIn('status', ['overdue', 'due_today', 'upcoming']);
        }

        // تصفية حسب المدينة
        if (!empty($filters['city'])) {
            $query->whereHas('device.address', function ($q) use ($filters) {
                $q->where('city', 'like', '%' . $filters['city'] . '%');
            });
        }

        // تصفية حسب العميل
        if (!empty($filters['customer_id'])) {
            $query->whereHas('device', function ($q) use ($filters) {
                $q->where('customer_id', $filters['customer_id']);
            });
        }

        // تصفية حسب رقم المرحلة
        if (!empty($filters['stage_number'])) {
            $query->where('stage_number', $filters['stage_number']);
        }

        // تصفية حسب موديل الجهاز
        if (!empty($filters['device_model_id'])) {
            $query->whereHas('device', function ($q) use ($filters) {
                $q->where('device_model_id', $filters['device_model_id']);
            });
        }

        // ترتيب الأولوية: المتأخرة أولاً ثم المستحقة اليوم ثم القادمة
        $query->orderBy('next_due_at', 'asc')
            ->orderBy('stage_number', 'asc');

        $paginated = $query->paginate($perPage);

        // تجهيز بيانات الاستجابة الغنية وفحص التذاكر المفتوحة
        $paginated->getCollection()->transform(function (CustomerDeviceStage $stage) use ($today) {
            $dueDate = $stage->next_due_at;
            $daysOverdue = ($dueDate && $dueDate->lessThan($today)) ? $dueDate->diffInDays($today) : 0;
            $hasOpenTicket = $this->hasActiveTicket($stage->customer_device_id);

            $device = $stage->device;
            $customer = $device?->customer;
            $address = $device?->address;
            $product = $stage->product;
            $model = $device?->model;

            return [
                'device_stage_id' => $stage->id,
                'customer_id' => $customer?->id,
                'customer_name' => $customer?->name,
                'customer_phone' => $customer?->primary_phone,
                'address' => $address ? "{$address->city} - {$address->district} - {$address->street}" : null,
                'device_id' => $device?->id,
                'device_code' => $device?->device_code,
                'device_model' => $model?->name,
                'stage_number' => $stage->stage_number,
                'stage_name_ar' => $product?->name_ar,
                'service_interval_days' => $stage->service_interval_days,
                'last_replacement_date' => $stage->last_actual_replacement_at?->toDateString(),
                'due_date' => $dueDate?->toDateString(),
                'days_overdue' => (int) $daysOverdue,
                'stage_status' => $stage->status,
                'has_open_ticket' => $hasOpenTicket,
            ];
        });

        return $paginated;
    }

    /**
     * منع التذاكر المزدوجة: فحص هل يوجد أمر صيانة نشط حالياً للجهاز
     */
    public function hasActiveTicket(int $customerDeviceId): bool
    {
        return MaintenanceTicket::where('customer_device_id', $customerDeviceId)
            ->whereNotIn('status', ['approved_closed', 'cancelled'])
            ->exists();
    }

    /**
     * تسجيل نتيجة تواصل خدمة العملاء وإنشاء تذكرة الصيانة عند الموافقة
     */
    public function recordInteraction(array $data, User $agent): array
    {
        return DB::transaction(function () use ($data, $agent) {
            $customer = Customer::findOrFail($data['customer_id']);
            $device = !empty($data['customer_device_id'])
                ? CustomerDevice::where('customer_id', $customer->id)->findOrFail($data['customer_device_id'])
                : $customer->devices()->where('status', 'active')->first();

            $outcome = $data['outcome'];
            $createdTicket = null;

            // 1. معالجة حالة الموافقة وجدولة الصيانة (مع منع التكرار الصارم)
            if ($outcome === 'agreed_scheduled' && !empty($data['create_ticket'])) {
                if (!$device) {
                    throw ValidationException::withMessages([
                        'customer_device_id' => 'يجب تحديد جهاز العميل لجدولة أمر الصيانة.'
                    ]);
                }

                // فحص منع التذاكر المزدوجة (Strict Duplicate Ticket Prevention)
                if ($this->hasActiveTicket($device->id)) {
                    throw ValidationException::withMessages([
                        'customer_device_id' => 'يوجد أمر صيانة نشط ومفتوح بالفعل لهذا الجهاز لمنع ازدواجية المهام.'
                    ]);
                }

                $ticketNumber = $this->generateTicketNumber();
                $addressId = $data['customer_address_id'] ?? $device->customer_address_id;

                if (!$addressId) {
                    $defaultAddr = $customer->addresses()->where('is_default', true)->first()
                        ?? $customer->addresses()->first();
                    $addressId = $defaultAddr?->id;
                }

                $technicianId = !empty($data['technician_id']) ? (int) $data['technician_id'] : null;

                $createdTicket = MaintenanceTicket::create([
                    'ticket_number' => $ticketNumber,
                    'customer_id' => $customer->id,
                    'customer_device_id' => $device->id,
                    'customer_address_id' => $addressId,
                    'technician_id' => $technicianId,
                    'service_type' => $data['service_type'] ?? 'routine_filter_change',
                    'priority' => $data['priority'] ?? 'normal',
                    'status' => $technicianId ? 'assigned' : 'scheduled',
                    'scheduled_date' => $data['scheduled_date'] ?? Carbon::tomorrow()->toDateString(),
                    'time_slot' => $data['time_slot'] ?? 'morning',
                    'notes' => $data['notes'] ?? null,
                ]);

                // ربط قطع الغيار والشمعات المطلوبة بأمر الصيانة
                if (!empty($data['requested_stages']) && is_array($data['requested_stages'])) {
                    $stages = $device->stages()
                        ->whereIn('stage_number', $data['requested_stages'])
                        ->with('product')
                        ->get();

                    foreach ($stages as $st) {
                        TicketSparePartUsed::create([
                            'maintenance_ticket_id' => $createdTicket->id,
                            'product_id' => $st->product_id,
                            'quantity' => 1,
                            'unit_price' => $st->product->selling_price ?? 0.0000,
                            'stage_number' => $st->stage_number,
                            'is_warranty_covered' => false,
                        ]);
                    }
                }
            }

            // 2. معالجة حالة التأجيل (تحديث حالة الشمعات وتاريخ التأجيل)
            if ($outcome === 'postponed' && !empty($data['postponed_until']) && $device) {
                $postponedUntil = Carbon::parse($data['postponed_until']);
                $stagesToPostpone = !empty($data['requested_stages'])
                    ? $device->stages()->whereIn('stage_number', $data['requested_stages'])->get()
                    : $device->stages()->whereIn('status', ['upcoming', 'due_today', 'overdue'])->get();

                $reason = $data['notes'] ?? 'تأجيل بناءً على طلب العميل';
                foreach ($stagesToPostpone as $stage) {
                    $this->lifecycleEngine->postponeStage($stage, $postponedUntil, $reason, $agent);
                }
            }

            // 3. حفظ سجل التواصل في customer_interactions
            $interaction = CustomerInteraction::create([
                'customer_id' => $customer->id,
                'customer_device_id' => $device?->id,
                'agent_id' => $agent->id,
                'maintenance_ticket_id' => $createdTicket?->id,
                'channel' => $data['channel'] ?? 'phone_call',
                'outcome' => $outcome,
                'follow_up_date' => $data['follow_up_date'] ?? $data['postponed_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
            ]);

            return [
                'interaction' => $interaction,
                'ticket' => $createdTicket,
            ];
        });
    }

    /**
     * توليد رقم تسلسلي لتذكرة الصيانة
     */
    protected function generateTicketNumber(): string
    {
        $year = date('Y');
        $count = MaintenanceTicket::whereYear('created_at', $year)->count() + 1;
        return sprintf('TCK-%s-%04d', $year, $count);
    }
}
