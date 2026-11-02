<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\GoodsReceiptNote;
use App\Models\GoodsReceiptNoteItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ErpSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('phone', '0500000001')->first() ?? User::first();
        $warehouse = Warehouse::where('code', 'WH-MAIN')->first() ?? Warehouse::first();
        $supplierDelta = Supplier::where('supplier_code', 'SUP-DELTA-01')->first();
        $supplierNaba = Supplier::where('supplier_code', 'SUP-NABA-02')->first();

        // 1. إضافة موردين إضافيين إن لم يكونوا موجودين
        if (!$supplierDelta) {
            $supplierDelta = Supplier::create([
                'supplier_code' => 'SUP-DELTA-01',
                'name' => 'شركة الدلتا الدولية لتجارة الفلاتر ومحطات التحلية',
                'contact_person' => 'م. طارق المحمود',
                'phone' => '0544332211',
                'email' => 'sales@delta-filters.local',
                'tax_number' => '310123456700003',
                'address' => 'الرياض - المنطقة الصناعية الثانية - مجمع التوريدات 4',
                'is_active' => true,
            ]);
        }

        if (!$supplierNaba) {
            $supplierNaba = Supplier::create([
                'supplier_code' => 'SUP-NABA-02',
                'name' => 'مؤسسة النبع الصافي لقطع الغيار والمضخات',
                'contact_person' => 'المهندس فاروق الدليمي',
                'phone' => '0511112233',
                'email' => 'farooq@nabatrading.local',
                'tax_number' => '300999888100003',
                'address' => 'جدة - حي الشرفية - شارع التحلية',
                'is_active' => true,
            ]);
        }

        $supplierAlAlamiyah = Supplier::firstOrCreate(
            ['supplier_code' => 'SUP-GLOBAL-03'],
            [
                'name' => 'شركة العالمية للتقنية المائية والغشاء الأسموزي (Membranes)',
                'contact_person' => 'أ. عبد الرحمن السويدي',
                'phone' => '0567890123',
                'email' => 'contact@global-watertech.local',
                'tax_number' => '300888777200003',
                'address' => 'الدمام - المدينة الصناعية الأولى',
                'is_active' => true,
            ]
        );

        $prodDevice = Product::where('sku', 'FLT-RO-CLASSIC-6')->first() ?? Product::first();
        $prodCartridge1 = Product::where('sku', 'FLT-PP-10')->first() ?? Product::skip(1)->first();
        $prodCartridge2 = Product::where('sku', 'FLT-GAC-10')->first() ?? Product::skip(2)->first();
        $prodPump = Product::where('sku', 'PMP-BOOSTER-75')->first() ?? Product::skip(3)->first();

        // 2. أوامر الشراء (Purchase Orders)
        if (PurchaseOrder::count() == 0 && $supplierDelta && $warehouse && $admin) {
            // أمر شراء 1: مستلم بالكامل مع سند استلام GRN
            $po1 = PurchaseOrder::create([
                'po_number' => 'PO-2026-0001',
                'supplier_id' => $supplierDelta->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'received',
                'total_amount' => 14500.0000,
                'order_date' => now()->subDays(15)->toDateString(),
                'notes' => 'توريد شحنة شمعات ومحطات تحلية للربع الأول',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id,
                'product_id' => $prodDevice->id,
                'ordered_qty' => 20,
                'received_qty' => 20,
                'unit_price' => 450.0000,
                'total_price' => 9000.0000,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id,
                'product_id' => $prodCartridge1->id,
                'ordered_qty' => 500,
                'received_qty' => 500,
                'unit_price' => 5.0000,
                'total_price' => 2500.0000,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po1->id,
                'product_id' => $prodPump->id,
                'ordered_qty' => 35,
                'received_qty' => 35,
                'unit_price' => 85.0000,
                'total_price' => 2975.0000,
            ]);

            // سند استلام مخزني مرتبط GRN
            $grn = GoodsReceiptNote::create([
                'grn_number' => 'GRN-2026-0001',
                'purchase_order_id' => $po1->id,
                'supplier_id' => $supplierDelta->id,
                'warehouse_id' => $warehouse->id,
                'received_date' => now()->subDays(12)->toDateString(),
                'notes' => 'تم الفحص المخزني ومطابقة المواصفات القياسية بنجاح',
                'received_by' => $admin->id,
            ]);

            GoodsReceiptNoteItem::create([
                'goods_receipt_note_id' => $grn->id,
                'product_id' => $prodDevice->id,
                'received_qty' => 20,
                'unit_cost' => 450.0000,
            ]);

            // أمر شراء 2: معتمد بانتظار التوريد
            $po2 = PurchaseOrder::create([
                'po_number' => 'PO-2026-0002',
                'supplier_id' => $supplierNaba->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'approved',
                'total_amount' => 8400.0000,
                'order_date' => now()->subDays(3)->toDateString(),
                'notes' => 'طلب شمعات كربون حبيبي ومضخات مساعدة إضافية',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id,
                'product_id' => $prodCartridge2->id,
                'ordered_qty' => 400,
                'received_qty' => 0,
                'unit_price' => 11.0000,
                'total_price' => 4400.0000,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po2->id,
                'product_id' => $prodPump->id,
                'ordered_qty' => 45,
                'received_qty' => 0,
                'unit_price' => 88.0000,
                'total_price' => 3960.0000,
            ]);

            // أمر شراء 3: مسودة للتسعير
            $po3 = PurchaseOrder::create([
                'po_number' => 'PO-2026-0003',
                'supplier_id' => $supplierAlAlamiyah->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'draft',
                'total_amount' => 5200.0000,
                'order_date' => now()->toDateString(),
                'notes' => 'عرض سعر توريد كميات إضافية من أغشية التناضح العكسي',
                'created_by' => $admin->id,
            ]);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po3->id,
                'product_id' => $prodCartridge1->id,
                'ordered_qty' => 300,
                'received_qty' => 0,
                'unit_price' => 6.0000,
                'total_price' => 1800.0000,
            ]);
        }

        // 3. عينات أوامر بيع (Sales Orders)
        $customer1 = Customer::first();
        if (SalesOrder::count() == 0 && $customer1 && $warehouse && $admin) {
            $so1 = SalesOrder::create([
                'order_number' => 'SO-2026-0001',
                'customer_id' => $customer1->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'confirmed',
                'subtotal' => 1100.0000,
                'discount_amount' => 50.0000,
                'tax_amount' => 157.5000,
                'grand_total' => 1207.5000,
                'order_date' => now()->subDays(2)->toDateString(),
                'notes' => 'طلب توريد وتركيب جهاز منزلي وشمعات تبديل احتياطية',
                'created_by' => $admin->id,
            ]);

            SalesOrderItem::create([
                'sales_order_id' => $so1->id,
                'product_id' => $prodDevice->id,
                'quantity' => 1,
                'unit_price' => 850.0000,
                'total_price' => 850.0000,
            ]);

            SalesOrderItem::create([
                'sales_order_id' => $so1->id,
                'product_id' => $prodCartridge1->id,
                'quantity' => 4,
                'unit_price' => 25.0000,
                'total_price' => 100.0000,
            ]);
        }
    }
}
