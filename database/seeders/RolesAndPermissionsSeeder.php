<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * ===================================================================
 * نظام الصلاحيات المتكامل - تقنية الفلاتر ERP
 * -------------------------------------------------------------------
 * مبني على المواصفات التفصيلية المعتمدة:
 *  - مالك النظام / Super Admin    : تحكم كامل لا قيود
 *  - مدير النظام                  : إدارة المستخدمين والإعدادات فقط
 *  - مدير المشتريات والمخزون       : المنتجات، الموردين، المخزون
 *  - مدير المبيعات                : فريق المبيعات، العملاء، الفواتير
 *  - موظف المبيعات               : إضافة عملاء + تسجيل بيع + فاتورة
 *  - مدير خدمة العملاء / موظفها   : قائمة العملاء، الفلاتر، تذاكر
 *  - مدير الصيانة                 : توزيع مهام، إسناد فنيين، اعتماد
 *  - الفني الميداني               : مهامه فقط - لا شيء خارجها
 *  - المحاسب                      : فواتير، مدفوعات، تقارير مالية
 * ===================================================================
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────────────────────
        // 1. الأدوار الرسمية المعتمدة في المنظومة
        // ─────────────────────────────────────────────────────────────
        $rolesData = [
            // مالك النظام - أعلى صلاحية مطلقة
            'super_admin' => [
                'display_name_ar' => 'مالك النظام / المدير العام',
                'description'     => 'أعلى مستوى صلاحية. يتحكم بكل شيء: المستخدمون، الأدوار، الإعدادات، السياسات، جميع العمليات.',
            ],
            // مدير النظام - إداري تقني بدون صلاحيات مالية تلقائية
            'system_admin' => [
                'display_name_ar' => 'مدير النظام',
                'description'     => 'إدارة الحسابات والأدوار والإعدادات وفق ما يمنحه مالك النظام. لا يصل للأرباح أو حذف الفواتير تلقائياً.',
            ],
            // مدير المشتريات والمخزون
            'procurement_manager' => [
                'display_name_ar' => 'مدير المشتريات والمخزون',
                'description'     => 'الموردون، المنتجات، الفلاتر والقطع، أوامر الشراء، استلام البضاعة، ومتابعة أرصدة المخزون.',
            ],
            // أمين المخزن (موظف مخزن عادي)
            'warehouse_keeper' => [
                'display_name_ar' => 'أمين المخزن',
                'description'     => 'تسجيل الاستلام والجرد. لا يعدل أسعار الشراء ولا يعتمد أوامر شراء.',
            ],
            // مدير المبيعات
            'sales_manager' => [
                'display_name_ar' => 'مدير المبيعات',
                'description'     => 'إدارة فريق المبيعات، عروض الأسعار، الفواتير، الموافقة على الخصومات، ومتابعة التقارير.',
            ],
            // موظف مبيعات
            'sales_rep' => [
                'display_name_ar' => 'موظف المبيعات',
                'description'     => 'إضافة العملاء، تسجيل البيع، إنشاء الفواتير. لا يرى الأرباح الإجمالية ولا يحذف فاتورة معتمدة.',
            ],
            // مدير خدمة العملاء (يشمل الموظف العادي أيضاً)
            'call_center' => [
                'display_name_ar' => 'موظف خدمة العملاء',
                'description'     => 'قائمة العملاء، استحقاقات الفلاتر، تسجيل نتائج الاتصال، إنشاء طلبات صيانة. لا يعدل مدة الفلاتر يدوياً.',
            ],
            // مدير الصيانة
            'maintenance_supervisor' => [
                'display_name_ar' => 'مدير الصيانة',
                'description'     => 'مشاهدة طلبات الصيانة، تعيين الفنيين، إعادة التوزيع، الأولويات، اعتماد الإغلاق، تقارير الأداء.',
            ],
            // الفني الميداني - صلاحيات محدودة جداً
            'technician' => [
                'display_name_ar' => 'الفني الميداني',
                'description'     => 'مهامه المسندة فقط. يرى بيانات العميل لمهمته، يسجل القطع المستبدلة، يرفع الصور، يكتب تقرير الصيانة.',
            ],
            // المحاسب
            'accountant' => [
                'display_name_ar' => 'المحاسب',
                'description'     => 'الفواتير، المبيعات والمشتريات مراجعة، تسجيل المدفوعات، أرصدة العملاء والموردين، التقارير المالية.',
            ],
            // مدير الشركة / المدير التنفيذي (قراءة شاملة فقط)
            'general_manager' => [
                'display_name_ar' => 'المدير التنفيذي',
                'description'     => 'استعراض التقارير الشاملة والمؤشرات التنفيذية عبر جميع الأقسام. لا يُدخل بيانات تشغيلية.',
            ],
            // العميل النهائي (مستخدم التطبيق)
            'customer' => [
                'display_name_ar' => 'العميل',
                'description'     => 'العميل النهائي مستخدم التطبيق لمتابعة أجهزته وفلاتره وطلبات الصيانة.',
            ],
        ];

        $roles = [];
        foreach ($rolesData as $name => $data) {
            $roles[$name] = Role::updateOrCreate(
                ['name' => $name],
                ['display_name_ar' => $data['display_name_ar'], 'description' => $data['description']]
            );
        }

        // ─────────────────────────────────────────────────────────────
        // 2. الصلاحيات التفصيلية المصنّفة بالوحدات
        // ─────────────────────────────────────────────────────────────
        $permsData = [

            // ──── إدارة المستخدمين والحسابات ────
            // مالك النظام أو مدير النظام المفوض فقط
            ['name' => 'users.view',           'display_name_ar' => 'عرض قائمة الموظفين وحساباتهم',             'module' => 'users'],
            ['name' => 'users.create',         'display_name_ar' => 'إنشاء حسابات موظفين جدد',                  'module' => 'users'],
            ['name' => 'users.update',         'display_name_ar' => 'تعديل بيانات المستخدمين',                   'module' => 'users'],
            ['name' => 'users.toggle_status',  'display_name_ar' => 'تفعيل وتعطيل حسابات الموظفين',             'module' => 'users'],
            ['name' => 'users.reset_password', 'display_name_ar' => 'إعادة تعيين كلمة مرور موظف',               'module' => 'users'],
            ['name' => 'users.assign_role',    'display_name_ar' => 'تعيين الدور الوظيفي للموظف',               'module' => 'users'],

            // ──── الأدوار والصلاحيات ────
            // مالك النظام فقط
            ['name' => 'roles.view',           'display_name_ar' => 'عرض الأدوار الوظيفية وصلاحياتها',          'module' => 'roles'],
            ['name' => 'roles.create',         'display_name_ar' => 'إنشاء أدوار وظيفية جديدة',                 'module' => 'roles'],
            ['name' => 'roles.update',         'display_name_ar' => 'تعديل أدوار وصلاحياتها',                   'module' => 'roles'],
            ['name' => 'roles.delete',         'display_name_ar' => 'حذف الأدوار المخصصة',                      'module' => 'roles'],
            ['name' => 'permissions.manage',   'display_name_ar' => 'إدارة الصلاحيات وتعيينها للأدوار',         'module' => 'roles'],

            // ──── إعدادات النظام والشركة ────
            ['name' => 'settings.view',        'display_name_ar' => 'عرض إعدادات النظام',                       'module' => 'settings'],
            ['name' => 'settings.manage',      'display_name_ar' => 'تعديل إعدادات الشركة والفروع والسياسات',   'module' => 'settings'],
            ['name' => 'audit.view',           'display_name_ar' => 'عرض سجل تدقيق العمليات الكامل',            'module' => 'settings'],

            // ──── العملاء ────
            ['name' => 'customers.view',       'display_name_ar' => 'عرض قائمة وملفات العملاء',                 'module' => 'customers'],
            ['name' => 'customers.create',     'display_name_ar' => 'إضافة عملاء جدد',                          'module' => 'customers'],
            ['name' => 'customers.update',     'display_name_ar' => 'تعديل بيانات العملاء',                     'module' => 'customers'],
            ['name' => 'customers.view_devices','display_name_ar' => 'عرض أجهزة العملاء وتاريخ التركيب',        'module' => 'customers'],

            // ──── كتالوج المنتجات والفلاتر ────
            // مدير المخزون أو موظف مخول
            ['name' => 'catalog.view',         'display_name_ar' => 'عرض المنتجات والفلاتر وموديلات الأجهزة',   'module' => 'catalog'],
            ['name' => 'catalog.manage',       'display_name_ar' => 'إضافة وتعديل المنتجات والفلاتر والقطع',    'module' => 'catalog'],
            ['name' => 'catalog.set_cycles',   'display_name_ar' => 'تحديد دورة استبدال كل قطعة أو فلتر',       'module' => 'catalog'],

            // ──── المخزون والمستودعات ────
            ['name' => 'inventory.view',       'display_name_ar' => 'عرض أرصدة وحركات المخزون',                 'module' => 'inventory'],
            ['name' => 'inventory.receive',    'display_name_ar' => 'تسجيل استلام البضاعة (GRN)',                'module' => 'inventory'],
            ['name' => 'inventory.adjust',     'display_name_ar' => 'إجراء تسويات وجرد مخزني',                  'module' => 'inventory'],
            ['name' => 'inventory.transfer',   'display_name_ar' => 'تحويل بين المستودعات وعهد الفنيين',        'module' => 'inventory'],

            // ──── الموردون والمشتريات ────
            // مدير المشتريات أو المخزون
            ['name' => 'suppliers.view',       'display_name_ar' => 'عرض دليل الموردين',                        'module' => 'procurement'],
            ['name' => 'suppliers.manage',     'display_name_ar' => 'إضافة وتعديل الموردين',                    'module' => 'procurement'],
            ['name' => 'procurement.view',     'display_name_ar' => 'عرض أوامر الشراء',                         'module' => 'procurement'],
            ['name' => 'procurement.create',   'display_name_ar' => 'تسجيل مشتريات وأوامر الشراء',              'module' => 'procurement'],
            ['name' => 'procurement.approve',  'display_name_ar' => 'اعتماد أوامر الشراء (المدير المفوض)',       'module' => 'procurement'],
            ['name' => 'procurement.receive',  'display_name_ar' => 'استلام البضاعة وتحديث المخزون',             'module' => 'procurement'],

            // ──── المبيعات والفوترة ────
            ['name' => 'sales.view',           'display_name_ar' => 'عرض أوامر البيع وعروض الأسعار',            'module' => 'sales'],
            ['name' => 'sales.create',         'display_name_ar' => 'إنشاء عروض أسعار وأوامر بيع',              'module' => 'sales'],
            ['name' => 'sales.approve_discount','display_name_ar' => 'الموافقة على الخصومات ضمن الحد المسموح',  'module' => 'sales'],
            ['name' => 'invoices.view',        'display_name_ar' => 'عرض الفواتير الضريبية',                    'module' => 'invoices'],
            ['name' => 'invoices.issue',       'display_name_ar' => 'إصدار فواتير ضريبية رسمية',                'module' => 'invoices'],
            ['name' => 'invoices.cancel',      'display_name_ar' => 'إلغاء فاتورة معتمدة (بموجب مستند)',        'module' => 'invoices'],
            ['name' => 'payments.view',        'display_name_ar' => 'عرض سندات القبض والمدفوعات',               'module' => 'invoices'],
            ['name' => 'payments.record',      'display_name_ar' => 'تسجيل سندات القبض والدفعات',               'module' => 'invoices'],

            // ──── خدمة العملاء والاستحقاقات ────
            ['name' => 'telesales.view',       'display_name_ar' => 'عرض استحقاقات الفلاتر والمتأخرين',         'module' => 'telesales'],
            ['name' => 'telesales.interact',   'display_name_ar' => 'تسجيل نتيجة الاتصال وجدولة المتابعة',      'module' => 'telesales'],
            ['name' => 'telesales.create_ticket','display_name_ar' => 'إنشاء طلب صيانة من شاشة خدمة العملاء',  'module' => 'telesales'],

            // ──── تذاكر الصيانة ────
            ['name' => 'tickets.view',         'display_name_ar' => 'عرض جميع تذاكر الصيانة',                   'module' => 'tickets'],
            ['name' => 'tickets.view_assigned','display_name_ar' => 'عرض المهام المسندة للفني شخصياً فقط',      'module' => 'tickets'],
            ['name' => 'tickets.create',       'display_name_ar' => 'إنشاء طلب صيانة جديد',                     'module' => 'tickets'],
            ['name' => 'tickets.assign',       'display_name_ar' => 'إسناد التذاكر للفنيين وإعادة التوزيع',     'module' => 'tickets'],
            ['name' => 'tickets.set_priority', 'display_name_ar' => 'تحديد أولوية مهام الصيانة',                'module' => 'tickets'],
            ['name' => 'tickets.execute',      'display_name_ar' => 'بدء وتنفيذ وتوثيق الصيانة ورفع الصور',    'module' => 'tickets'],
            ['name' => 'tickets.approve',      'display_name_ar' => 'مراجعة واعتماد إغلاق مهمة الصيانة',        'module' => 'tickets'],
            ['name' => 'tickets.rework',       'display_name_ar' => 'إعادة مهمة للفني للتصحيح',                 'module' => 'tickets'],

            // ──── المحاسبة وشجرة الحسابات ────
            ['name' => 'accounting.view',      'display_name_ar' => 'عرض دليل الحسابات وشجرة الحسابات والقيود', 'module' => 'accounting'],
            ['name' => 'accounting.manage',    'display_name_ar' => 'إدارة شجرة الحسابات وتعديل الحسابات',      'module' => 'accounting'],
            ['name' => 'accounting.entries',   'display_name_ar' => 'إنشاء ومراجعة القيود اليومية المحاسبية',   'module' => 'accounting'],

            // ──── التقارير ────
            ['name' => 'reports.view',         'display_name_ar' => 'عرض التقارير التشغيلية (حسب صلاحية القسم)','module' => 'reports'],
            ['name' => 'reports.financial',    'display_name_ar' => 'عرض التقارير المالية والأرباح والخسائر',   'module' => 'reports'],
            ['name' => 'reports.print',        'display_name_ar' => 'طباعة وتصدير التقارير PDF',                 'module' => 'reports'],
            ['name' => 'reports.full',         'display_name_ar' => 'الوصول للتقارير الشاملة عبر جميع الأقسام', 'module' => 'reports'],
        ];

        $perms = [];
        foreach ($permsData as $p) {
            $perms[$p['name']] = Permission::updateOrCreate(
                ['name' => $p['name']],
                ['display_name_ar' => $p['display_name_ar'], 'module' => $p['module']]
            );
        }

        // ─────────────────────────────────────────────────────────────
        // 3. ربط الصلاحيات بالأدوار بدقة تامة وفق المواصفة
        // ─────────────────────────────────────────────────────────────

        // ══════════════════════════════════════════════════════════════
        // مالك النظام / Super Admin → كل الصلاحيات بلا استثناء
        // ══════════════════════════════════════════════════════════════
        $roles['super_admin']->permissions()->sync(Permission::pluck('id')->toArray());

        // ══════════════════════════════════════════════════════════════
        // مدير النظام → إدارة المستخدمين، الأدوار، الإعدادات
        //               لا يصل للأرباح أو حذف الفواتير تلقائياً
        // ══════════════════════════════════════════════════════════════
        $roles['system_admin']->permissions()->sync([
            $perms['users.view']->id,
            $perms['users.create']->id,
            $perms['users.update']->id,
            $perms['users.toggle_status']->id,
            $perms['users.reset_password']->id,
            $perms['users.assign_role']->id,
            $perms['roles.view']->id,
            $perms['settings.view']->id,
            $perms['settings.manage']->id,
            $perms['audit.view']->id,
            $perms['customers.view']->id,      // يرى العملاء للمساعدة فقط
            $perms['reports.view']->id,        // تقارير تشغيلية فقط لا مالية
        ]);

        // ══════════════════════════════════════════════════════════════
        // مدير المشتريات والمخزون
        // ══════════════════════════════════════════════════════════════
        $roles['procurement_manager']->permissions()->sync([
            $perms['catalog.view']->id,
            $perms['catalog.manage']->id,
            $perms['catalog.set_cycles']->id,
            $perms['inventory.view']->id,
            $perms['inventory.receive']->id,
            $perms['inventory.adjust']->id,
            $perms['inventory.transfer']->id,
            $perms['suppliers.view']->id,
            $perms['suppliers.manage']->id,
            $perms['procurement.view']->id,
            $perms['procurement.create']->id,
            $perms['procurement.approve']->id,
            $perms['procurement.receive']->id,
            $perms['reports.view']->id,
            $perms['reports.print']->id,
        ]);

        // ══════════════════════════════════════════════════════════════
        // أمين المخزن → تسجيل استلام وجرد فقط
        //               لا يعدل أسعار ولا يعتمد أوامر شراء
        // ══════════════════════════════════════════════════════════════
        $roles['warehouse_keeper']->permissions()->sync([
            $perms['catalog.view']->id,
            $perms['inventory.view']->id,
            $perms['inventory.receive']->id,
            $perms['inventory.adjust']->id,
            $perms['inventory.transfer']->id,
            $perms['suppliers.view']->id,
            $perms['procurement.view']->id,
            $perms['procurement.receive']->id,
        ]);

        // ══════════════════════════════════════════════════════════════
        // مدير المبيعات
        // ══════════════════════════════════════════════════════════════
        $roles['sales_manager']->permissions()->sync([
            $perms['customers.view']->id,
            $perms['customers.create']->id,
            $perms['customers.update']->id,
            $perms['customers.view_devices']->id,
            $perms['catalog.view']->id,
            $perms['sales.view']->id,
            $perms['sales.create']->id,
            $perms['sales.approve_discount']->id,
            $perms['invoices.view']->id,
            $perms['invoices.issue']->id,
            $perms['payments.view']->id,
            $perms['payments.record']->id,
            $perms['reports.view']->id,
            $perms['reports.financial']->id,   // يرى الأرباح والمبيعات
            $perms['reports.print']->id,
        ]);

        // ══════════════════════════════════════════════════════════════
        // موظف المبيعات → يضيف عملاء، يبيع، ينشئ فواتير
        //                 لا يرى الأرباح الإجمالية
        //                 لا يحذف فاتورة معتمدة
        // ══════════════════════════════════════════════════════════════
        $roles['sales_rep']->permissions()->sync([
            $perms['customers.view']->id,
            $perms['customers.create']->id,
            $perms['customers.update']->id,
            $perms['catalog.view']->id,
            $perms['sales.view']->id,
            $perms['sales.create']->id,
            $perms['invoices.view']->id,
            $perms['invoices.issue']->id,
            $perms['payments.view']->id,
            // لا يملك: reports.financial - invoices.cancel - sales.approve_discount
        ]);

        // ══════════════════════════════════════════════════════════════
        // موظف خدمة العملاء
        //   - يتصل بالعملاء، يسجل نتيجة المكالمة
        //   - يشاهد استحقاقات الفلاتر
        //   - ينشئ طلب صيانة
        //   - لا يعدل مدة الفلتر يدوياً
        //   - لا يعدل فواتير
        // ══════════════════════════════════════════════════════════════
        $roles['call_center']->permissions()->sync([
            $perms['customers.view']->id,
            $perms['customers.create']->id,
            $perms['customers.view_devices']->id,
            $perms['telesales.view']->id,
            $perms['telesales.interact']->id,
            $perms['telesales.create_ticket']->id,
            $perms['tickets.view']->id,
            $perms['tickets.create']->id,
            $perms['tickets.assign']->id,
            // لا يملك: catalog.set_cycles - invoices - reports.financial
        ]);

        // ══════════════════════════════════════════════════════════════
        // مدير الصيانة
        //   - يرى جميع التذاكر
        //   - يعيّن الفنيين ويُعيد التوزيع
        //   - يعتمد الإغلاق
        //   - يستخرج تقارير الأداء
        // ══════════════════════════════════════════════════════════════
        $roles['maintenance_supervisor']->permissions()->sync([
            $perms['customers.view']->id,
            $perms['customers.view_devices']->id,
            $perms['catalog.view']->id,
            $perms['inventory.view']->id,          // يتابع القطع المتوفرة
            $perms['tickets.view']->id,
            $perms['tickets.create']->id,
            $perms['tickets.assign']->id,
            $perms['tickets.set_priority']->id,
            $perms['tickets.approve']->id,
            $perms['tickets.rework']->id,
            $perms['reports.view']->id,
            $perms['reports.print']->id,
        ]);

        // ══════════════════════════════════════════════════════════════
        // الفني الميداني → صلاحيات محدودة جداً
        //   - يرى مهامه المسندة فقط
        //   - يبدأ المهمة ويسجل القطع المستبدلة
        //   - يرفع الصور ويكتب تقرير الصيانة
        //   لا يرى: جميع العملاء - الفواتير - المخزون العام
        //   لا يحذف مهام - لا يغير صلاحيات
        // ══════════════════════════════════════════════════════════════
        $roles['technician']->permissions()->sync([
            $perms['tickets.view_assigned']->id,
            $perms['tickets.execute']->id,
            $perms['catalog.view']->id,            // يرى القطع المطلوب استبدالها
        ]);

        // ══════════════════════════════════════════════════════════════
        // المحاسب
        //   - يضيف ويدير المشتريات والموردين
        //   - ينشئ عروض وأوامر البيع ويصدر فواتير المبيعات
        //   - يجرد المخزون ويجري التسويات
        //   - يضيف ويعدل العملاء
        //   - إدارة الحسابات والقيود وشجرة الحسابات
        // ══════════════════════════════════════════════════════════════
        $roles['accountant']->permissions()->sync([
            $perms['customers.view']->id,
            $perms['customers.create']->id,
            $perms['customers.update']->id,
            $perms['suppliers.view']->id,
            $perms['suppliers.manage']->id,
            $perms['procurement.view']->id,
            $perms['procurement.create']->id,
            $perms['procurement.approve']->id,
            $perms['procurement.receive']->id,
            $perms['sales.view']->id,
            $perms['sales.create']->id,
            $perms['invoices.view']->id,
            $perms['invoices.issue']->id,
            $perms['payments.view']->id,
            $perms['payments.record']->id,
            $perms['inventory.view']->id,
            $perms['inventory.adjust']->id,
            $perms['accounting.view']->id,
            $perms['accounting.manage']->id,
            $perms['accounting.entries']->id,
            $perms['reports.view']->id,
            $perms['reports.financial']->id,
            $perms['reports.print']->id,
        ]);

        // ══════════════════════════════════════════════════════════════
        // المدير العام / المدير التنفيذي → إدارة تنفيذية وإشراف وإضافة كاملة
        // ══════════════════════════════════════════════════════════════
        $roles['general_manager']->permissions()->sync([
            $perms['customers.view']->id,
            $perms['customers.create']->id,
            $perms['customers.update']->id,
            $perms['customers.view_devices']->id,
            $perms['catalog.view']->id,
            $perms['catalog.manage']->id,
            $perms['inventory.view']->id,
            $perms['inventory.receive']->id,
            $perms['inventory.adjust']->id,
            $perms['inventory.transfer']->id,
            $perms['suppliers.view']->id,
            $perms['suppliers.manage']->id,
            $perms['procurement.view']->id,
            $perms['procurement.create']->id,
            $perms['procurement.approve']->id,
            $perms['procurement.receive']->id,
            $perms['sales.view']->id,
            $perms['sales.create']->id,
            $perms['sales.approve_discount']->id,
            $perms['invoices.view']->id,
            $perms['invoices.issue']->id,
            $perms['payments.view']->id,
            $perms['payments.record']->id,
            $perms['tickets.view']->id,
            $perms['tickets.create']->id,
            $perms['tickets.assign']->id,
            $perms['tickets.approve']->id,
            $perms['telesales.view']->id,
            $perms['telesales.interact']->id,
            $perms['users.view']->id,
            $perms['users.create']->id,
            $perms['users.update']->id,
            $perms['accounting.view']->id,
            $perms['accounting.manage']->id,
            $perms['accounting.entries']->id,
            $perms['reports.view']->id,
            $perms['reports.financial']->id,
            $perms['reports.full']->id,
            $perms['reports.print']->id,
            $perms['audit.view']->id,
        ]);

        // ══════════════════════════════════════════════════════════════
        // العميل → تطبيق الجوال فقط، لا صلاحيات في لوحة الإدارة
        // ══════════════════════════════════════════════════════════════
        $roles['customer']->permissions()->sync([]);
    }
}
