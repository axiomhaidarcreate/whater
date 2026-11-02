<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class WebAuthController extends Controller
{
    /**
     * عرض شاشة تسجيل الدخول
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // جلب قائمة المستخدمين التجريبيين لتسهيل التبديل السريع بضغطة زر
        $demoUsers = User::with('roles')
            ->whereIn('phone', [
                '0500000001', // super_admin
                '0500000008', // maintenance_supervisor
                '0500000003', // call_center
                '0500000002', // technician
                '0500000005', // warehouse_keeper
                '0500000006', // accountant
            ])
            ->get();

        return view('auth.login', compact('demoUsers'));
    }

    /**
     * معالجة تسجيل الدخول اليدوي
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $credentials['phone'])->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'auth.web_login',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'new_values' => ['role' => $user->roles->first()?->name ?? 'user'],
                'created_at' => now(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'phone' => 'رقم الجوال أو كلمة المرور غير صحيحة.',
        ])->onlyInput('phone');
    }

    /**
     * تبديل الحساب السريع بنقرة واحدة (للمعاينة وتجربة الصلاحيات)
     */
    public function switchRole(Request $request, string $phone)
    {
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            $defaultRoles = [
                '0500000001' => ['name' => 'مالك النظام (المدير العام)', 'role' => 'super_admin', 'type' => 'staff'],
                '0500000008' => ['name' => 'مشرف الصيانة (المهندس عادل)', 'role' => 'maintenance_supervisor', 'type' => 'staff'],
                '0500000002' => ['name' => 'فني صيانة ميداني (أحمد)', 'role' => 'technician', 'type' => 'technician'],
                '0500000003' => ['name' => 'خدمة العملاء (سارة)', 'role' => 'call_center', 'type' => 'staff'],
                '0500000005' => ['name' => 'أمين المستودع (سليمان)', 'role' => 'warehouse_keeper', 'type' => 'staff'],
                '0500000006' => ['name' => 'المحاسب المالي (طارق)', 'role' => 'accountant', 'type' => 'staff'],
            ];

            if (isset($defaultRoles[$phone])) {
                $meta = $defaultRoles[$phone];
                $user = User::create([
                    'phone' => $phone,
                    'name' => $meta['name'],
                    'email' => "user_{$phone}@marswater.local",
                    'password' => Hash::make('Admin@123456'),
                    'user_type' => $meta['type'],
                    'is_active' => true,
                ]);

                $role = \App\Models\Role::where('name', $meta['role'])->first();
                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }
            } else {
                return redirect()->route('dashboard')->with('error', 'الحساب غير موجود في النظام.');
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        $roleName = $user->roles->first()?->display_name_ar ?? $user->roles->first()?->name ?? 'مستخدم';

        return redirect()->route('dashboard')->with('success', "تم الدخول بنجاح بحساب: {$user->name} ({$roleName})");
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        if (Auth::check()) {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'auth.web_logout',
                'auditable_type' => User::class,
                'auditable_id' => Auth::id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح.');
    }
}
