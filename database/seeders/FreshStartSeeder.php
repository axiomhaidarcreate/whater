<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\SystemSetting;

/**
 * =============================================================
 * FreshStartSeeder – مسح البيانات التجريبية بالكامل مع الإبقاء
 * على الصلاحيات والأدوار وإعادة إنشاء مالك النظام فقط.
 * =============================================================
 */
class FreshStartSeeder extends Seeder
{
    public function run(): void
    {
        // ─── إيقاف فحص المفاتيح الأجنبية مؤقتاً (MySQL/MariaDB) ───
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // ─── مسح البيانات التشغيلية بالترتيب الصحيح ───
        DB::table('notification_logs')->truncate();
        DB::table('customer_interactions')->truncate();
        DB::table('ticket_attachments')->truncate();
        DB::table('ticket_spare_parts_used')->truncate();
        DB::table('maintenance_tickets')->truncate();
        DB::table('stage_replacement_history')->truncate();
        DB::table('customer_device_stages')->truncate();
        DB::table('customer_devices')->truncate();
        DB::table('invoice_items')->truncate();
        DB::table('payments')->truncate();
        DB::table('invoices')->truncate();
        DB::table('sales_order_items')->truncate();
        DB::table('sales_orders')->truncate();
        DB::table('customer_addresses')->truncate();
        DB::table('customers')->truncate();
        DB::table('goods_receipt_note_items')->truncate();
        DB::table('goods_receipt_notes')->truncate();
        DB::table('purchase_order_items')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('stock_movement_items')->truncate();
        DB::table('stock_movements')->truncate();
        DB::table('stock_balances')->truncate();
        DB::table('suppliers')->truncate();
        DB::table('warehouses')->truncate();
        DB::table('device_model_stages')->truncate();
        DB::table('device_models')->truncate();
        DB::table('products')->truncate();
        DB::table('categories')->truncate();
        DB::table('audit_logs')->truncate();
        DB::table('user_device_tokens')->truncate();

        // ─── حذف كل المستخدمين (سيُعاد إنشاء مالك النظام فقط) ───
        DB::table('role_user')->truncate();
        DB::table('users')->truncate();

        // ─── تفعيل فحص المفاتيح الأجنبية ───
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // ─── إنشاء حسابات الأدوار الرئيسية للتجربة والتشغيل ───
        $rolesToSeed = [
            [
                'name'      => 'مالك النظام (المدير العام)',
                'phone'     => '0500000001',
                'password'  => Hash::make('Admin@1234'),
                'user_type' => 'staff',
                'role'      => 'super_admin',
            ],
            [
                'name'      => 'مشرف الصيانة (المهندس عادل)',
                'phone'     => '0500000008',
                'password'  => Hash::make('Admin@1234'),
                'user_type' => 'staff',
                'role'      => 'maintenance_supervisor',
            ],
            [
                'name'      => 'فني صيانة ميداني (أحمد)',
                'phone'     => '0500000002',
                'password'  => Hash::make('Admin@1234'),
                'user_type' => 'technician',
                'role'      => 'technician',
            ],
            [
                'name'      => 'خدمة العملاء (سارة)',
                'phone'     => '0500000003',
                'password'  => Hash::make('Admin@1234'),
                'user_type' => 'staff',
                'role'      => 'call_center',
            ],
            [
                'name'      => 'أمين المستودع (سليمان)',
                'phone'     => '0500000005',
                'password'  => Hash::make('Admin@1234'),
                'user_type' => 'staff',
                'role'      => 'warehouse_keeper',
            ],
            [
                'name'      => 'المحاسب المالي (طارق)',
                'phone'     => '0500000006',
                'password'  => Hash::make('Admin@1234'),
                'user_type' => 'staff',
                'role'      => 'accountant',
            ],
        ];

        foreach ($rolesToSeed as $acc) {
            $u = User::create([
                'name'      => $acc['name'],
                'phone'     => $acc['phone'],
                'password'  => $acc['password'],
                'user_type' => $acc['user_type'],
                'is_active' => true,
            ]);

            $r = Role::where('name', $acc['role'])->first();
            if ($r) {
                $u->roles()->sync([$r->id]);
            }
        }

        $this->command->info('✅ تم مسح البيانات التجريبية بنجاح.');
        $this->command->info('👑 تم إنشاء حسابات تجربة الأدوار (6 حسابات رئيسية).');
    }
}
