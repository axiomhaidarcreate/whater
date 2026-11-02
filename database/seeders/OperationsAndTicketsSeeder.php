<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDevice;
use App\Models\CustomerDeviceStage;
use App\Models\CustomerInteraction;
use App\Models\DeviceModel;
use App\Models\MaintenanceTicket;
use App\Models\Product;
use App\Models\StageReplacementHistory;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\FilterLifecycleEngine;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OperationsAndTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $engine = app(FilterLifecycleEngine::class);
        $technician = User::where('phone', '0500000002')->first();
        $supervisor = User::where('phone', '0500000008')->first();
        $callCenter = User::where('phone', '0500000003')->first();
        $admin = User::where('phone', '0500000001')->first();
        $model6Stage = DeviceModel::where('model_code', 'MOD-CLASSIC-6')->first() ?? DeviceModel::first();
        $model7Stage = $model6Stage;

        // 1. تفعيل جهاز العميل الأول (خالد السعيد) إذا كان معلقاً
        $khaledDevice = CustomerDevice::where('serial_number', 'SN-RO-2026-98741')->first();
        if ($khaledDevice) {
            $khaledDevice->update([
                'status' => 'active',
                'installation_date' => now()->subMonths(6)->toDateString(),
                'warranty_expiry_date' => now()->addMonths(18)->toDateString(),
            ]);

            if ($khaledDevice->stages()->count() === 0) {
                $engine->initializeDeviceStages($khaledDevice, now()->subMonths(6));
            }

            // تعديل مواعيد المراحل لتوليد حالات واقعية (مستحق اليوم، منتهي الصلاحية، قادم)
            $stages = $khaledDevice->stages()->orderBy('stage_number')->get();
            if ($stages->count() >= 3) {
                // الشمعة 1: مستحقة اليوم
                $stages[0]->update([
                    'status' => 'due_today',
                    'next_due_at' => now()->toDateString(),
                ]);
                // الشمعة 2: متأخرة 5 أيام
                $stages[1]->update([
                    'status' => 'overdue',
                    'next_due_at' => now()->subDays(5)->toDateString(),
                ]);
                // الشمعة 3: قادمة خلال أسبوعين
                $stages[2]->update([
                    'status' => 'upcoming',
                    'next_due_at' => now()->addDays(12)->toDateString(),
                ]);
            }
        }

        // 2. إنشاء عميل ثانٍ نشط (سعد العتيبي - حي النرجس)
        $customer2 = Customer::firstOrCreate(
            ['primary_phone' => '0554433221'],
            [
                'customer_code' => 'CUST-000103',
                'name' => 'سعد عبد الله العتيبي',
                'secondary_phone' => '0501234987',
                'email' => 'saad.otb@gmail.com',
                'lead_source' => 'توصية عميل سابق',
                'notes' => 'فيلا دوبلكس - جهاز تحلية 7 مراحل في المطبخ الرئيسي',
                'is_active' => true,
            ]
        );

        $address2 = CustomerAddress::firstOrCreate(
            ['customer_id' => $customer2->id, 'title' => 'الفيلا الرئيسية'],
            [
                'city' => 'الرياض',
                'district' => 'حي النرجس',
                'street' => 'شارع عثمان بن عفان مخرج 7',
                'latitude' => 24.845120,
                'longitude' => 46.685320,
                'is_default' => true,
            ]
        );

        $device2 = CustomerDevice::firstOrCreate(
            ['serial_number' => 'SN-RO-2026-55441'],
            [
                'device_code' => 'DEV-2026-0002',
                'customer_id' => $customer2->id,
                'customer_address_id' => $address2->id,
                'device_model_id' => $model7Stage->id,
                'installation_date' => now()->subMonths(9)->toDateString(),
                'warranty_expiry_date' => now()->addMonths(15)->toDateString(),
                'status' => 'active',
                'notes' => 'جهاز بريميوم 7 مراحل مع شاشة قياس رقمية',
            ]
        );

        if ($device2->stages()->count() === 0) {
            $engine->initializeDeviceStages($device2, now()->subMonths(9));
        }

        // 3. إنشاء عميل ثالث (شركة الأفق للتقنية)
        $customer3 = Customer::firstOrCreate(
            ['primary_phone' => '0114889900'],
            [
                'customer_code' => 'CUST-000104',
                'name' => 'شركة أفق للحلول الرقمية',
                'secondary_phone' => '0533221144',
                'email' => 'operations@ofooq-tech.sa',
                'lead_source' => 'مبيعات الشركات B2B',
                'notes' => 'مكتب إداري - صيانة نصف سنوية دورية',
                'is_active' => true,
            ]
        );

        $address3 = CustomerAddress::firstOrCreate(
            ['customer_id' => $customer3->id, 'title' => 'المقر الرئيسي'],
            [
                'city' => 'الرياض',
                'district' => 'حي السليمانية',
                'street' => 'طريق العروبة - مبنى 402',
                'latitude' => 24.708890,
                'longitude' => 46.699450,
                'is_default' => true,
            ]
        );

        $device3 = CustomerDevice::firstOrCreate(
            ['serial_number' => 'SN-RO-2026-88123'],
            [
                'device_code' => 'DEV-2026-0003',
                'customer_id' => $customer3->id,
                'customer_address_id' => $address3->id,
                'device_model_id' => $model6Stage->id,
                'installation_date' => now()->subMonths(3)->toDateString(),
                'warranty_expiry_date' => now()->addMonths(21)->toDateString(),
                'status' => 'active',
                'notes' => 'مركب في ركن المشروبات بالطابق الثاني',
            ]
        );

        if ($device3->stages()->count() === 0) {
            $engine->initializeDeviceStages($device3, now()->subMonths(3));
        }

        // 4. إنشاء تذاكر صيانة تشغيلية متنوعة الحالات
        $ticket1 = MaintenanceTicket::updateOrCreate(
            ['ticket_number' => 'TKT-2026-0001'],
            [
                'customer_id' => $khaledDevice->customer_id ?? 1,
                'customer_device_id' => $khaledDevice->id ?? 1,
                'customer_address_id' => $khaledDevice->customer_address_id ?? 1,
                'service_type' => 'routine_filter_change',
                'priority' => 'high',
                'status' => 'assigned',
                'technician_id' => $technician?->id,
                'supervisor_id' => $supervisor?->id,
                'scheduled_date' => now()->toDateString(),
                'time_slot' => 'صباحاً (09:00 - 12:00)',
                'notes' => 'استبدال شمعات المرحلة الأولى والثانية المستحقة وفحص نسبة الأملاح TDS',
            ]
        );

        $ticket2 = MaintenanceTicket::updateOrCreate(
            ['ticket_number' => 'TKT-2026-0002'],
            [
                'customer_id' => $customer2->id,
                'customer_device_id' => $device2->id,
                'customer_address_id' => $address2->id,
                'service_type' => 'repair',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'technician_id' => $technician?->id,
                'supervisor_id' => $supervisor?->id,
                'scheduled_date' => now()->toDateString(),
                'time_slot' => 'ظهراً (12:00 - 03:00)',
                'arrived_at' => now()->subMinutes(25),
                'notes' => 'بلاغ تسريب مياه خفيف عند محبس الدخول الرئيسي - الفني في الموقع حالياً',
            ]
        );

        $ticket3 = MaintenanceTicket::updateOrCreate(
            ['ticket_number' => 'TKT-2026-0003'],
            [
                'customer_id' => $customer3->id,
                'customer_device_id' => $device3->id,
                'customer_address_id' => $address3->id,
                'service_type' => 'warranty_check',
                'priority' => 'normal',
                'status' => 'new',
                'supervisor_id' => $supervisor?->id,
                'scheduled_date' => now()->addDay()->toDateString(),
                'time_slot' => 'مساءً (04:00 - 07:00)',
                'notes' => 'طلب العميل قياس نسبة الأملاح الذائبة بعد انتهاء فترة تجربة الجهاز 3 أشهر',
            ]
        );

        $ticket4 = MaintenanceTicket::updateOrCreate(
            ['ticket_number' => 'TKT-2026-0004'],
            [
                'customer_id' => $customer2->id,
                'customer_device_id' => $device2->id,
                'customer_address_id' => $address2->id,
                'service_type' => 'routine_filter_change',
                'priority' => 'normal',
                'status' => 'approved_closed',
                'technician_id' => $technician?->id,
                'supervisor_id' => $supervisor?->id,
                'scheduled_date' => now()->subDays(2)->toDateString(),
                'time_slot' => 'صباحاً (09:00 - 12:00)',
                'arrived_at' => now()->subDays(2)->setTime(10, 15),
                'completed_at' => now()->subDays(2)->setTime(11, 40),
                'total_cost' => 180.00,
                'tds_input' => 480,
                'tds_output' => 38,
                'customer_rating' => 5,
                'notes' => 'تم استبدال شمعات المرحلة 1 و 2 و 3 بنجاح. النتيجة ممتازة وتم توقيع العميل إلكترونياً.',
            ]
        );

        // 5. سجل تفاعلات خدمة العملاء
        CustomerInteraction::create([
            'customer_id' => $khaledDevice->customer_id ?? 1,
            'customer_device_id' => $khaledDevice->id ?? null,
            'agent_id' => $callCenter?->id ?? 1,
            'channel' => 'phone_call',
            'outcome' => 'agreed_scheduled',
            'notes' => 'تم الاتصال بالعميل وإبلاغه باستحقاق شمعة المرحلة الأولى. وافق على الموعد الصباحي اليوم.',
            'created_at' => now()->subHours(3),
        ]);

        CustomerInteraction::create([
            'customer_id' => $customer2->id,
            'customer_device_id' => $device2->id,
            'agent_id' => $callCenter?->id ?? 1,
            'channel' => 'phone_call',
            'outcome' => 'agreed_scheduled',
            'notes' => 'اتصل العميل للإبلاغ عن تسريب مياه خفيف. تم فتح تذكرة طارئة وإسنادها فوراً للفني سالم.',
            'created_at' => now()->subHours(1),
        ]);

        // 6. سجلات تدقيق Audit Logs
        AuditLog::create([
            'user_id' => $supervisor?->id ?? 1,
            'action' => 'ticket.assigned',
            'auditable_type' => MaintenanceTicket::class,
            'auditable_id' => $ticket1->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 FilterTech Web Dashboard',
            'new_values' => [
                'technician' => $technician?->name ?? 'سالم الشمري',
                'time_slot' => 'صباحاً (09:00 - 12:00)',
            ],
            'created_at' => now()->subHours(2),
        ]);

        AuditLog::create([
            'user_id' => $technician?->id ?? 1,
            'action' => 'ticket.arrived',
            'auditable_type' => MaintenanceTicket::class,
            'auditable_id' => $ticket2->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'FilterTech Technician Mobile App/v1.0',
            'new_values' => [
                'arrived_at' => now()->subMinutes(25)->toDateTimeString(),
            ],
            'created_at' => now()->subMinutes(25),
        ]);
    }
}
