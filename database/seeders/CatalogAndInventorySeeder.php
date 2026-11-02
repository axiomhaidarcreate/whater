<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DeviceModel;
use App\Models\DeviceModelStage;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class CatalogAndInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $inventoryService = app(InventoryService::class);
        $admin = User::where('phone', '0500000001')->first();

        // 1. التصنيفات
        $categories = [
            'devices' => Category::updateOrCreate(['code' => 'devices'], ['name_ar' => 'أجهزة ومحطات التحلية']),
            'cartridges' => Category::updateOrCreate(['code' => 'cartridges'], ['name_ar' => 'شمعات وفلاتر استهلاكية']),
            'membranes' => Category::updateOrCreate(['code' => 'membranes'], ['name_ar' => 'أغشية التناضح العكسي (ممبرين)']),
            'pumps' => Category::updateOrCreate(['code' => 'pumps'], ['name_ar' => 'مضخات ومحولات']),
            'spare_parts' => Category::updateOrCreate(['code' => 'spare_parts'], ['name_ar' => 'قطع غيار وملحقات']),
            'services' => Category::updateOrCreate(['code' => 'services'], ['name_ar' => 'خدمات تركيب وصيانة']),
        ];

        // 2. المنتجات
        $productsData = [
            'FLT-RO-CLASSIC-6' => [
                'category_id' => $categories['devices']->id,
                'name_ar' => 'محطة تحلية منزلية 6 مراحل كلاسيك',
                'type' => 'water_device',
                'cost_price' => 450.0000,
                'selling_price' => 850.0000,
                'reorder_level' => 10,
            ],
            'FLT-RO-SMART-7' => [
                'category_id' => $categories['devices']->id,
                'name_ar' => 'محطة تحلية ذكية 7 مراحل ديجيتال',
                'type' => 'water_device',
                'cost_price' => 650.0000,
                'selling_price' => 1250.0000,
                'reorder_level' => 5,
            ],
            'FLT-PP-10' => [
                'category_id' => $categories['cartridges']->id,
                'name_ar' => 'المرحلة 1 - شمعة قطن بولي بروبلين 10 بوصة',
                'type' => 'filter_cartridge',
                'cost_price' => 5.0000,
                'selling_price' => 25.0000,
                'reorder_level' => 50,
            ],
            'FLT-GAC-10' => [
                'category_id' => $categories['cartridges']->id,
                'name_ar' => 'المرحلة 2 - شمعة كربون حبيبي نشط 10 بوصة',
                'type' => 'filter_cartridge',
                'cost_price' => 8.0000,
                'selling_price' => 35.0000,
                'reorder_level' => 40,
            ],
            'FLT-CTO-10' => [
                'category_id' => $categories['cartridges']->id,
                'name_ar' => 'المرحلة 3 - شمعة كربون مسمط صلب 10 بوصة',
                'type' => 'filter_cartridge',
                'cost_price' => 9.0000,
                'selling_price' => 35.0000,
                'reorder_level' => 40,
            ],
            'FLT-RO-MEM-75' => [
                'category_id' => $categories['membranes']->id,
                'name_ar' => 'المرحلة 4 - غشاء ممبرين أمريكي 75 جالون',
                'type' => 'membrane',
                'cost_price' => 65.0000,
                'selling_price' => 160.0000,
                'reorder_level' => 20,
            ],
            'FLT-POST-T33' => [
                'category_id' => $categories['cartridges']->id,
                'name_ar' => 'المرحلة 5 - شمعة بوست كربون لإزالة الطعم والرائحة',
                'type' => 'filter_cartridge',
                'cost_price' => 12.0000,
                'selling_price' => 40.0000,
                'reorder_level' => 30,
            ],
            'FLT-MINERAL-4IN1' => [
                'category_id' => $categories['cartridges']->id,
                'name_ar' => 'المرحلة 6 - شمعة المعادن والأملاح الطبيعية',
                'type' => 'filter_cartridge',
                'cost_price' => 18.0000,
                'selling_price' => 60.0000,
                'reorder_level' => 25,
            ],
            'PMP-BOOSTER-75' => [
                'category_id' => $categories['pumps']->id,
                'name_ar' => 'مضخة تقوية لفلتر التحلية 75 جالون',
                'type' => 'pump',
                'cost_price' => 85.0000,
                'selling_price' => 180.0000,
                'reorder_level' => 10,
            ],
            'SRV-RO-INSTALL' => [
                'category_id' => $categories['services']->id,
                'name_ar' => 'خدمة تركيب محطة تحلية منزلية',
                'type' => 'service',
                'cost_price' => 50.0000,
                'selling_price' => 150.0000,
                'reorder_level' => 0,
            ],
        ];

        $products = [];
        foreach ($productsData as $sku => $data) {
            $products[$sku] = Product::updateOrCreate(
                ['sku' => $sku],
                array_merge($data, ['is_active' => true])
            );
        }

        // 3. موديل الجهاز وقائمة المكونات (BOM)
        $classicModel = DeviceModel::updateOrCreate(
            ['model_code' => 'MOD-CLASSIC-6'],
            [
                'name' => 'محطة التحلية 6 مراحل كلاسيك',
                'stages_count' => 6,
                'description' => 'محطة تحلية مياه منزلية كلاسيكية 6 مراحل مع مضخة وخزان فيبر 19 لتر.',
                'is_active' => true,
            ]
        );

        $bomStages = [
            ['stage_number' => 1, 'stage_name_ar' => 'شمعة قطن بولي بروبلين', 'product_id' => $products['FLT-PP-10']->id, 'default_lifespan_days' => 90],
            ['stage_number' => 2, 'stage_name_ar' => 'شمعة كربون حبيبي', 'product_id' => $products['FLT-GAC-10']->id, 'default_lifespan_days' => 180],
            ['stage_number' => 3, 'stage_name_ar' => 'شمعة كربون صلب مسمط', 'product_id' => $products['FLT-CTO-10']->id, 'default_lifespan_days' => 180],
            ['stage_number' => 4, 'stage_name_ar' => 'غشاء الممبرين', 'product_id' => $products['FLT-RO-MEM-75']->id, 'default_lifespan_days' => 720],
            ['stage_number' => 5, 'stage_name_ar' => 'شمعة بوست كربون', 'product_id' => $products['FLT-POST-T33']->id, 'default_lifespan_days' => 360],
            ['stage_number' => 6, 'stage_name_ar' => 'شمعة المعادن', 'product_id' => $products['FLT-MINERAL-4IN1']->id, 'default_lifespan_days' => 360],
        ];

        foreach ($bomStages as $stage) {
            DeviceModelStage::updateOrCreate(
                [
                    'device_model_id' => $classicModel->id,
                    'stage_number' => $stage['stage_number'],
                ],
                [
                    'stage_name_ar' => $stage['stage_name_ar'],
                    'product_id' => $stage['product_id'],
                    'default_lifespan_days' => $stage['default_lifespan_days'],
                    'is_mandatory' => true,
                ]
            );
        }

        // 4. المستودعات
        $technicianUser = User::where('phone', '0500000002')->first();

        $mainWarehouse = Warehouse::updateOrCreate(
            ['code' => 'WH-MAIN'],
            ['name' => 'المستودع المركزي - الرياض', 'type' => 'central', 'is_active' => true]
        );

        $branchWarehouse = Warehouse::updateOrCreate(
            ['code' => 'WH-JED'],
            ['name' => 'مستودع فرع جدة', 'type' => 'branch', 'is_active' => true]
        );

        $techVanWarehouse = Warehouse::updateOrCreate(
            ['code' => 'WH-VAN-01'],
            [
                'name' => 'سيارة الفني أحمد (فورد ترانزيت)',
                'type' => 'technician_van',
                'assigned_user_id' => $technicianUser?->id,
                'is_active' => true,
            ]
        );

        // 5. المورد
        Supplier::updateOrCreate(
            ['supplier_code' => 'SUP-001'],
            [
                'name' => 'شركة نبع المياه للتوريدات التجارية',
                'contact_person' => 'المهندس فاروق',
                'phone' => '0511112233',
                'email' => 'sales@nabatrading.local',
                'tax_number' => '300999888100003',
                'is_active' => true,
            ]
        );

        // 6. الرصيد الافتتاحي الموثق في المستودع المركزي
        $initialStock = [
            'FLT-RO-CLASSIC-6' => 50,
            'FLT-PP-10' => 500,
            'FLT-GAC-10' => 300,
            'FLT-CTO-10' => 300,
            'FLT-RO-MEM-75' => 150,
            'FLT-POST-T33' => 200,
            'FLT-MINERAL-4IN1' => 200,
            'PMP-BOOSTER-75' => 60,
        ];

        foreach ($initialStock as $sku => $qty) {
            $prod = $products[$sku];
            $inventoryService->increaseStock(
                warehouse: $mainWarehouse,
                product: $prod,
                quantity: $qty,
                unitCost: (float) $prod->cost_price,
                movementType: 'purchase_receipt',
                reference: null,
                notes: 'رصيد افتتاحي معتمد للمستودع المركزي',
                user: $admin
            );
        }

        // عهدة أولية لسيارة الفني أحمد من المستودع المركزي
        $inventoryService->transferStock(
            fromWarehouse: $mainWarehouse,
            toWarehouse: $techVanWarehouse,
            items: [
                ['product_id' => $products['FLT-PP-10']->id, 'quantity' => 20],
                ['product_id' => $products['FLT-GAC-10']->id, 'quantity' => 15],
                ['product_id' => $products['FLT-CTO-10']->id, 'quantity' => 15],
                ['product_id' => $products['FLT-POST-T33']->id, 'quantity' => 10],
            ],
            notes: 'صرف عهدة الصيانة الدورية الأسبوعية لسيارة الفني أحمد',
            user: $admin
        );
    }
}
