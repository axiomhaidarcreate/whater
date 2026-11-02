<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesAndInvoicingService;
use Illuminate\Database\Seeder;

class CustomersAndSalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salesRep = User::where('phone', '0500000007')->first();
        $admin = User::where('phone', '0500000001')->first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first();
        $invoicingService = app(SalesAndInvoicingService::class);

        // 1. إنشاء عميل رئيسي (خالد السعيد) مربوط بحسابه التجريبي
        $customerUser = User::where('phone', '0500000004')->first();

        $customer1 = Customer::updateOrCreate(
            ['primary_phone' => '0500000004'],
            [
                'customer_code' => 'CUST-000101',
                'user_id' => $customerUser?->id,
                'name' => 'خالد ناصر السعيد',
                'secondary_phone' => '0551122334',
                'email' => 'customer.khaled@filtertech.local',
                'lead_source' => 'معرض المبيعات الرئيسي',
                'sales_rep_id' => $salesRep?->id,
                'notes' => 'عميل فيلا سكنية - مهتم بجودة المياه وطلب فحص الأملاح الدورية.',
                'is_active' => true,
            ]
        );

        $address1 = CustomerAddress::updateOrCreate(
            ['customer_id' => $customer1->id, 'title' => 'المنزل الرئيسي'],
            [
                'city' => 'الرياض',
                'district' => 'حي الياسمين',
                'street' => 'شارع القادسية مخرج 5',
                'latitude' => 24.819384,
                'longitude' => 46.639102,
                'is_default' => true,
            ]
        );

        // 2. إنشاء عميل ثانٍ (شركة النخبة للاستشارات)
        $customer2 = Customer::updateOrCreate(
            ['primary_phone' => '0509988776'],
            [
                'customer_code' => 'CUST-000102',
                'name' => 'مكتب النخبة للاستشارات القانونية',
                'secondary_phone' => '0112345678',
                'email' => 'contact@nukhba-law.local',
                'lead_source' => 'الموقع الإلكتروني',
                'sales_rep_id' => $salesRep?->id,
                'notes' => 'مقر تجاري - يحتاج صيانة دورية شهرية.',
                'is_active' => true,
            ]
        );

        CustomerAddress::updateOrCreate(
            ['customer_id' => $customer2->id, 'title' => 'المكتب الرئيسي'],
            [
                'city' => 'الرياض',
                'district' => 'حي العليا',
                'street' => 'طريق الملك فهد - برج العليا',
                'latitude' => 24.713552,
                'longitude' => 46.675296,
                'is_default' => true,
            ]
        );

        // 3. بيع جهاز تحلية منزلي كلاسيك 6 مراحل للعميل خالد السعيد
        $deviceProduct = Product::where('sku', 'FLT-RO-CLASSIC-6')->first();
        $cartridgeProduct = Product::where('sku', 'FLT-PP-10')->first();

        if ($deviceProduct && $cartridgeProduct && $warehouse) {
            $invoicingService->issueInvoice(
                customer: $customer1,
                warehouse: $warehouse,
                items: [
                    [
                        'product_id' => $deviceProduct->id,
                        'quantity' => 1,
                        'unit_price' => (float) $deviceProduct->selling_price,
                        'discount' => 50.00,
                        'serial_number' => 'SN-RO-2026-98741',
                    ],
                    [
                        'product_id' => $cartridgeProduct->id,
                        'quantity' => 2,
                        'unit_price' => (float) $cartridgeProduct->selling_price,
                        'discount' => 0.00,
                    ],
                ],
                invoiceDate: now()->toDateString(),
                address: $address1,
                salesOrder: null,
                initialPayment: [
                    'amount' => 500.00,
                    'payment_method' => 'mada_pos',
                    'transaction_reference' => 'POS-TX-987123',
                ],
                user: $salesRep ?? $admin
            );
        }
    }
}
