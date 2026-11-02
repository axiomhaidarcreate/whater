<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'company_name',
                'value' => 'شركة تقنية الفلاتر لأنظمة التحلية والصيانة',
                'group' => 'company',
                'type' => 'string',
                'description_ar' => 'اسم المنشأة التجاري المعتمد',
                'is_public' => true,
            ],
            [
                'key' => 'company_phone',
                'value' => '920012345',
                'group' => 'company',
                'type' => 'string',
                'description_ar' => 'هاتف خدمة العملاء الموحد',
                'is_public' => true,
            ],
            [
                'key' => 'company_email',
                'value' => 'info@filtertech.local',
                'group' => 'company',
                'type' => 'string',
                'description_ar' => 'البريد الإلكتروني الرسمي',
                'is_public' => true,
            ],
            [
                'key' => 'tax_number',
                'value' => '300000000000003',
                'group' => 'financial',
                'type' => 'string',
                'description_ar' => 'الرقم الضريبي المعتمد للمنشأة',
                'is_public' => true,
            ],
            [
                'key' => 'vat_percentage',
                'value' => '15',
                'group' => 'financial',
                'type' => 'integer',
                'description_ar' => 'نسبة ضريبة القيمة المضافة (%)',
                'is_public' => true,
            ],
            [
                'key' => 'default_currency',
                'value' => 'SAR',
                'group' => 'financial',
                'type' => 'string',
                'description_ar' => 'العملة الأساسية للنظام',
                'is_public' => true,
            ],
            [
                'key' => 'timezone',
                'value' => 'Asia/Riyadh',
                'group' => 'general',
                'type' => 'string',
                'description_ar' => 'المنطقة الزمنية التشغيلية',
                'is_public' => false,
            ],
            [
                'key' => 'reminder_window_days',
                'value' => '14',
                'group' => 'maintenance',
                'type' => 'integer',
                'description_ar' => 'نافذة التنبيه المسبق لاستحقاق الفلاتر بالأيام',
                'is_public' => false,
            ],
            [
                'key' => 'working_hours_start',
                'value' => '08:00',
                'group' => 'operations',
                'type' => 'string',
                'description_ar' => 'بداية وردية العمل الميداني',
                'is_public' => false,
            ],
            [
                'key' => 'working_hours_end',
                'value' => '20:00',
                'group' => 'operations',
                'type' => 'string',
                'description_ar' => 'نهاية وردية العمل الميداني',
                'is_public' => false,
            ],
            // ──── إعدادات الألوان والمظهر ────
            [
                'key' => 'theme_accent_primary',
                'value' => '#38bdf8',
                'group' => 'appearance',
                'type' => 'string',
                'description_ar' => 'اللون الأساسي للنظام (Accent Primary)',
                'is_public' => true,
            ],
            [
                'key' => 'theme_accent_secondary',
                'value' => '#10b981',
                'group' => 'appearance',
                'type' => 'string',
                'description_ar' => 'اللون الثانوي (Accent Secondary)',
                'is_public' => true,
            ],
            [
                'key' => 'theme_bg_base',
                'value' => '#060b17',
                'group' => 'appearance',
                'type' => 'string',
                'description_ar' => 'لون خلفية النظام الرئيسية',
                'is_public' => true,
            ],
            [
                'key' => 'theme_sidebar_bg',
                'value' => '#0a1124',
                'group' => 'appearance',
                'type' => 'string',
                'description_ar' => 'لون خلفية القائمة الجانبية',
                'is_public' => true,
            ],
            [
                'key' => 'theme_preset',
                'value' => 'ocean_blue',
                'group' => 'appearance',
                'type' => 'string',
                'description_ar' => 'الثيم المختار: ocean_blue | emerald_night | purple_galaxy | sunset_amber | crimson_dark',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::set(
                key: $setting['key'],
                value: $setting['value'],
                group: $setting['group'],
                type: $setting['type'],
                descriptionAr: $setting['description_ar'],
                isPublic: $setting['is_public']
            );
        }
    }
}
