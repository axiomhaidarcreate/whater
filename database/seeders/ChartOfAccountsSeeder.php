<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

/**
 * ChartOfAccountsSeeder
 * =================================================================
 * يُنشئ شجرة الحسابات الافتراضية المعيارية لشركة تقنية الفلاتر
 * مبنية على المعيار المحاسبي السعودي وطبيعة عمليات الشركة:
 *  1xxx → الأصول (Assets)
 *  2xxx → الخصوم (Liabilities)
 *  3xxx → حقوق الملكية (Equity)
 *  4xxx → الإيرادات (Revenue)
 *  5xxx → المصروفات (Expenses)
 * =================================================================
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('جاري بناء شجرة الحسابات الافتراضية...');

        $accounts = $this->getAccountTree();

        foreach ($accounts as $parentData) {
            $parent = $this->createAccount($parentData, null, 1);
            if (!empty($parentData['children'])) {
                foreach ($parentData['children'] as $groupData) {
                    $group = $this->createAccount($groupData, $parent, 2);
                    if (!empty($groupData['children'])) {
                        foreach ($groupData['children'] as $subData) {
                            $this->createAccount($subData, $group, 3);
                        }
                    }
                }
            }
        }

        $this->command->info('✅ تم إنشاء شجرة الحسابات بنجاح.');
    }

    protected function createAccount(array $data, ?Account $parent, int $level): Account
    {
        $account = Account::updateOrCreate(
            ['code' => $data['code']],
            [
                'name_ar'            => $data['name_ar'],
                'name_en'            => $data['name_en'] ?? null,
                'account_type'       => $data['account_type'],
                'normal_balance'     => $data['normal_balance'],
                'parent_id'          => $parent?->id,
                'level'              => $level,
                'is_posting_account' => $data['is_posting'] ?? true,
                'is_system'          => $data['is_system'] ?? true,
                'is_active'          => true,
                'notes'              => $data['notes'] ?? null,
            ]
        );

        // إذا له أبناء → يصبح تجميعياً
        if (!empty($data['children'])) {
            $account->update(['is_posting_account' => false]);
        }

        return $account;
    }

    protected function getAccountTree(): array
    {
        return [
            // ═══════════════════════════════════════════════════
            // 1. الأصول (Assets) — طبيعتها مدينة
            // ═══════════════════════════════════════════════════
            [
                'code' => '1000', 'name_ar' => 'الأصول', 'name_en' => 'Assets',
                'account_type' => 'asset', 'normal_balance' => 'debit',
                'is_posting' => false, 'is_system' => true,
                'children' => [
                    [
                        'code' => '1100', 'name_ar' => 'الأصول المتداولة', 'name_en' => 'Current Assets',
                        'account_type' => 'asset', 'normal_balance' => 'debit',
                        'is_posting' => false, 'is_system' => true,
                        'children' => [
                            ['code' => '1110', 'name_ar' => 'الصندوق (نقدية بالخزينة)', 'name_en' => 'Cash on Hand',           'account_type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
                            ['code' => '1120', 'name_ar' => 'البنك الرئيسي',             'name_en' => 'Main Bank Account',      'account_type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
                            ['code' => '1121', 'name_ar' => 'نقاط البيع (مدى / POS)',    'name_en' => 'POS / Mada',             'account_type' => 'asset', 'normal_balance' => 'debit'],
                            ['code' => '1130', 'name_ar' => 'ذمم مدينة (عملاء)',         'name_en' => 'Accounts Receivable',    'account_type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
                            ['code' => '1140', 'name_ar' => 'المخزون — فلاتر وقطع غيار', 'name_en' => 'Inventory',              'account_type' => 'asset', 'normal_balance' => 'debit', 'is_system' => true],
                            ['code' => '1141', 'name_ar' => 'مخزون عهدة الفنيين (سيارات)', 'name_en' => 'Technician Van Stock', 'account_type' => 'asset', 'normal_balance' => 'debit'],
                            ['code' => '1150', 'name_ar' => 'مصاريف مدفوعة مقدماً',      'name_en' => 'Prepaid Expenses',       'account_type' => 'asset', 'normal_balance' => 'debit'],
                            ['code' => '1160', 'name_ar' => 'ضريبة القيمة المضافة — مدخلات (15%)', 'name_en' => 'VAT Input',   'account_type' => 'asset', 'normal_balance' => 'debit'],
                        ],
                    ],
                    [
                        'code' => '1200', 'name_ar' => 'الأصول الثابتة', 'name_en' => 'Fixed Assets',
                        'account_type' => 'asset', 'normal_balance' => 'debit',
                        'is_posting' => false, 'is_system' => true,
                        'children' => [
                            ['code' => '1210', 'name_ar' => 'أثاث وتجهيزات مكتبية',          'name_en' => 'Furniture & Fixtures',   'account_type' => 'asset', 'normal_balance' => 'debit'],
                            ['code' => '1211', 'name_ar' => 'م.تراكم إهلاك — أثاث',           'name_en' => 'Accum. Depr. Furniture', 'account_type' => 'asset', 'normal_balance' => 'credit'],
                            ['code' => '1220', 'name_ar' => 'أجهزة حاسب وبرامج',             'name_en' => 'Computer & Software',    'account_type' => 'asset', 'normal_balance' => 'debit'],
                            ['code' => '1221', 'name_ar' => 'م.تراكم إهلاك — حاسب',          'name_en' => 'Accum. Depr. Computer',  'account_type' => 'asset', 'normal_balance' => 'credit'],
                            ['code' => '1230', 'name_ar' => 'سيارات (مركبات الفنيين)',        'name_en' => 'Vehicles',               'account_type' => 'asset', 'normal_balance' => 'debit'],
                            ['code' => '1231', 'name_ar' => 'م.تراكم إهلاك — سيارات',        'name_en' => 'Accum. Depr. Vehicles',  'account_type' => 'asset', 'normal_balance' => 'credit'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════
            // 2. الخصوم (Liabilities) — طبيعتها دائنة
            // ═══════════════════════════════════════════════════
            [
                'code' => '2000', 'name_ar' => 'الخصوم', 'name_en' => 'Liabilities',
                'account_type' => 'liability', 'normal_balance' => 'credit',
                'is_posting' => false, 'is_system' => true,
                'children' => [
                    [
                        'code' => '2100', 'name_ar' => 'الخصوم المتداولة', 'name_en' => 'Current Liabilities',
                        'account_type' => 'liability', 'normal_balance' => 'credit',
                        'is_posting' => false, 'is_system' => true,
                        'children' => [
                            ['code' => '2110', 'name_ar' => 'ذمم دائنة (موردون)',                    'name_en' => 'Accounts Payable',        'account_type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '2120', 'name_ar' => 'ضريبة القيمة المضافة — مخرجات (15%)', 'name_en' => 'VAT Output',              'account_type' => 'liability', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '2121', 'name_ar' => 'صافي ضريبة القيمة المضافة المستحقة',  'name_en' => 'VAT Payable Net',         'account_type' => 'liability', 'normal_balance' => 'credit'],
                            ['code' => '2130', 'name_ar' => 'رواتب وأجور مستحقة',                  'name_en' => 'Accrued Salaries',        'account_type' => 'liability', 'normal_balance' => 'credit'],
                            ['code' => '2140', 'name_ar' => 'مصاريف مستحقة أخرى',                  'name_en' => 'Other Accrued Expenses',  'account_type' => 'liability', 'normal_balance' => 'credit'],
                            ['code' => '2150', 'name_ar' => 'إيرادات مقدمة (عقود صيانة)',           'name_en' => 'Deferred Revenue',        'account_type' => 'liability', 'normal_balance' => 'credit'],
                            ['code' => '2160', 'name_ar' => 'قروض قصيرة الأجل',                    'name_en' => 'Short-term Loans',        'account_type' => 'liability', 'normal_balance' => 'credit'],
                        ],
                    ],
                    [
                        'code' => '2200', 'name_ar' => 'الخصوم طويلة الأجل', 'name_en' => 'Non-current Liabilities',
                        'account_type' => 'liability', 'normal_balance' => 'credit',
                        'is_posting' => false,
                        'children' => [
                            ['code' => '2210', 'name_ar' => 'قروض طويلة الأجل',   'name_en' => 'Long-term Loans',          'account_type' => 'liability', 'normal_balance' => 'credit'],
                            ['code' => '2220', 'name_ar' => 'التزامات مكافأة نهاية الخدمة', 'name_en' => 'End-of-Service Provision', 'account_type' => 'liability', 'normal_balance' => 'credit'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════
            // 3. حقوق الملكية (Equity) — طبيعتها دائنة
            // ═══════════════════════════════════════════════════
            [
                'code' => '3000', 'name_ar' => 'حقوق الملكية', 'name_en' => 'Equity',
                'account_type' => 'equity', 'normal_balance' => 'credit',
                'is_posting' => false, 'is_system' => true,
                'children' => [
                    [
                        'code' => '3100', 'name_ar' => 'رأس المال', 'name_en' => 'Capital',
                        'account_type' => 'equity', 'normal_balance' => 'credit',
                        'is_posting' => false, 'is_system' => true,
                        'children' => [
                            ['code' => '3110', 'name_ar' => 'رأس المال المدفوع',    'name_en' => 'Paid-in Capital',       'account_type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '3120', 'name_ar' => 'احتياطي قانوني',       'name_en' => 'Legal Reserve',         'account_type' => 'equity', 'normal_balance' => 'credit'],
                            ['code' => '3130', 'name_ar' => 'أرباح محتجزة',         'name_en' => 'Retained Earnings',     'account_type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '3140', 'name_ar' => 'ملخص الأرباح والخسائر', 'name_en' => 'Income Summary',       'account_type' => 'equity', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '3150', 'name_ar' => 'مسحوبات المالك',        'name_en' => "Owner's Drawings",     'account_type' => 'equity', 'normal_balance' => 'debit'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════
            // 4. الإيرادات (Revenue) — طبيعتها دائنة
            // ═══════════════════════════════════════════════════
            [
                'code' => '4000', 'name_ar' => 'الإيرادات', 'name_en' => 'Revenue',
                'account_type' => 'revenue', 'normal_balance' => 'credit',
                'is_posting' => false, 'is_system' => true,
                'children' => [
                    [
                        'code' => '4100', 'name_ar' => 'إيرادات المبيعات', 'name_en' => 'Sales Revenue',
                        'account_type' => 'revenue', 'normal_balance' => 'credit',
                        'is_posting' => false, 'is_system' => true,
                        'children' => [
                            ['code' => '4110', 'name_ar' => 'مبيعات الأجهزة ومحطات التحلية',  'name_en' => 'Device & Unit Sales',       'account_type' => 'revenue', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '4120', 'name_ar' => 'مبيعات فلاتر وقطع الغيار',       'name_en' => 'Filter & Spare Parts Sales', 'account_type' => 'revenue', 'normal_balance' => 'credit'],
                            ['code' => '4130', 'name_ar' => 'إيرادات خدمات الصيانة',          'name_en' => 'Maintenance Service Revenue','account_type' => 'revenue', 'normal_balance' => 'credit', 'is_system' => true],
                            ['code' => '4140', 'name_ar' => 'إيرادات عقود الصيانة الدورية',   'name_en' => 'AMC Contract Revenue',      'account_type' => 'revenue', 'normal_balance' => 'credit'],
                            ['code' => '4150', 'name_ar' => 'خصم مسموح به (مردود مبيعات)',    'name_en' => 'Sales Discount Allowed',    'account_type' => 'revenue', 'normal_balance' => 'debit'],
                        ],
                    ],
                    [
                        'code' => '4200', 'name_ar' => 'إيرادات أخرى', 'name_en' => 'Other Income',
                        'account_type' => 'revenue', 'normal_balance' => 'credit',
                        'is_posting' => false,
                        'children' => [
                            ['code' => '4210', 'name_ar' => 'إيرادات فوائد بنكية',   'name_en' => 'Bank Interest Income',  'account_type' => 'revenue', 'normal_balance' => 'credit'],
                            ['code' => '4220', 'name_ar' => 'أرباح بيع أصول',        'name_en' => 'Gain on Asset Disposal','account_type' => 'revenue', 'normal_balance' => 'credit'],
                            ['code' => '4230', 'name_ar' => 'إيرادات متنوعة',        'name_en' => 'Miscellaneous Income',  'account_type' => 'revenue', 'normal_balance' => 'credit'],
                        ],
                    ],
                ],
            ],

            // ═══════════════════════════════════════════════════
            // 5. المصروفات (Expenses) — طبيعتها مدينة
            // ═══════════════════════════════════════════════════
            [
                'code' => '5000', 'name_ar' => 'المصروفات', 'name_en' => 'Expenses',
                'account_type' => 'expense', 'normal_balance' => 'debit',
                'is_posting' => false, 'is_system' => true,
                'children' => [
                    [
                        'code' => '5100', 'name_ar' => 'تكلفة المبيعات (COGS)', 'name_en' => 'Cost of Goods Sold',
                        'account_type' => 'expense', 'normal_balance' => 'debit',
                        'is_posting' => false, 'is_system' => true,
                        'children' => [
                            ['code' => '5110', 'name_ar' => 'تكلفة الأجهزة المباعة',       'name_en' => 'COGS — Devices',           'account_type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
                            ['code' => '5120', 'name_ar' => 'تكلفة الفلاتر وقطع الغيار',  'name_en' => 'COGS — Filters & Parts',   'account_type' => 'expense', 'normal_balance' => 'debit', 'is_system' => true],
                            ['code' => '5130', 'name_ar' => 'تكلفة خدمات الصيانة',        'name_en' => 'COGS — Maintenance Service','account_type' => 'expense', 'normal_balance' => 'debit'],
                        ],
                    ],
                    [
                        'code' => '5200', 'name_ar' => 'مصاريف التشغيل', 'name_en' => 'Operating Expenses',
                        'account_type' => 'expense', 'normal_balance' => 'debit',
                        'is_posting' => false,
                        'children' => [
                            ['code' => '5210', 'name_ar' => 'رواتب وأجور الموظفين',        'name_en' => 'Salaries & Wages',        'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5211', 'name_ar' => 'بدل مواصلات وانتقال الفنيين', 'name_en' => 'Technician Travel Allow.', 'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5220', 'name_ar' => 'إيجار المستودع والمكتب',      'name_en' => 'Rent Expense',            'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5230', 'name_ar' => 'كهرباء وماء ومواصلات',        'name_en' => 'Utilities',               'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5240', 'name_ar' => 'وقود سيارات الفنيين',          'name_en' => 'Vehicle Fuel',            'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5250', 'name_ar' => 'مصاريف تسويق وإعلان',         'name_en' => 'Marketing & Advertising', 'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5260', 'name_ar' => 'مصاريف اشتراكات وبرمجيات',    'name_en' => 'Subscriptions & Software','account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5270', 'name_ar' => 'إهلاك الأصول الثابتة',         'name_en' => 'Depreciation Expense',    'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5280', 'name_ar' => 'خسائر وإتلاف مخزون',          'name_en' => 'Inventory Write-off',     'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5290', 'name_ar' => 'مصاريف إدارية متنوعة',        'name_en' => 'General Admin Expenses',  'account_type' => 'expense', 'normal_balance' => 'debit'],
                        ],
                    ],
                    [
                        'code' => '5300', 'name_ar' => 'المصاريف المالية', 'name_en' => 'Financial Expenses',
                        'account_type' => 'expense', 'normal_balance' => 'debit',
                        'is_posting' => false,
                        'children' => [
                            ['code' => '5310', 'name_ar' => 'فوائد وعمولات بنكية',      'name_en' => 'Bank Charges & Interest',  'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5320', 'name_ar' => 'عمولات بوابات الدفع',       'name_en' => 'Payment Gateway Fees',     'account_type' => 'expense', 'normal_balance' => 'debit'],
                            ['code' => '5330', 'name_ar' => 'خسارة العملات الأجنبية',    'name_en' => 'FX Loss',                  'account_type' => 'expense', 'normal_balance' => 'debit'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
