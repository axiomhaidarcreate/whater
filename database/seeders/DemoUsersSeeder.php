<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUsers = [
            [
                'name' => '[تجريبي] مدير النظام',
                'phone' => '0500000001',
                'email' => 'admin.demo@filtertech.local',
                'password' => 'Admin@123456',
                'user_type' => 'staff',
                'role' => 'super_admin',
            ],
            [
                'name' => '[تجريبي] فني صيانة ميداني (أحمد)',
                'phone' => '0500000002',
                'email' => 'tech.ahmed@filtertech.local',
                'password' => 'Tech@123456',
                'user_type' => 'technician',
                'role' => 'technician',
            ],
            [
                'name' => '[تجريبي] موظف خدمة عملاء (سارة)',
                'phone' => '0500000003',
                'email' => 'callcenter.sara@filtertech.local',
                'password' => 'Staff@123456',
                'user_type' => 'staff',
                'role' => 'call_center',
            ],
            [
                'name' => '[تجريبي] العميل (خالد السعيد)',
                'phone' => '0500000004',
                'email' => 'customer.khaled@filtertech.local',
                'password' => 'Customer@123456',
                'user_type' => 'customer',
                'role' => 'customer',
            ],
            [
                'name' => '[تجريبي] أمين المخزن (سليمان)',
                'phone' => '0500000005',
                'email' => 'warehouse.sulaiman@filtertech.local',
                'password' => 'Staff@123456',
                'user_type' => 'staff',
                'role' => 'warehouse_keeper',
            ],
            [
                'name' => '[تجريبي] المحاسب (طارق)',
                'phone' => '0500000006',
                'email' => 'accountant.tariq@filtertech.local',
                'password' => 'Staff@123456',
                'user_type' => 'staff',
                'role' => 'accountant',
            ],
            [
                'name' => '[تجريبي] موظف المبيعات (فهد)',
                'phone' => '0500000007',
                'email' => 'sales.fahad@filtertech.local',
                'password' => 'Staff@123456',
                'user_type' => 'staff',
                'role' => 'sales_rep',
            ],
            [
                'name' => '[تجريبي] مشرف الصيانة (المهندس عادل)',
                'phone' => '0500000008',
                'email' => 'supervisor.adel@filtertech.local',
                'password' => 'Staff@123456',
                'user_type' => 'staff',
                'role' => 'maintenance_supervisor',
            ],
        ];

        foreach ($demoUsers as $data) {
            $roleName = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['phone' => $data['phone']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'user_type' => $data['user_type'],
                    'is_active' => true,
                ]
            );

            $user->assignRole($roleName);
        }
    }
}
