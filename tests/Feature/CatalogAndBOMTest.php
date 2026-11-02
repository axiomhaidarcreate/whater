<?php

namespace Tests\Feature;

use App\Models\DeviceModel;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogAndBOMTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_authenticated_user_can_list_products_and_filter_by_type(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/products?type=water_device');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $prod) {
            $this->assertEquals('water_device', $prod['type']);
        }
    }

    public function test_user_can_view_single_product_with_warehouse_breakdown(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::where('sku', 'FLT-PP-10')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sku' => 'FLT-PP-10',
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'total_stock',
                    'warehouse_balances',
                ],
            ]);
    }

    public function test_admin_can_create_new_product(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/products', [
                'sku' => 'FLT-NEW-NANO',
                'name_ar' => 'شمعة النانو سيلفر المضادة للبكتيريا',
                'type' => 'filter_cartridge',
                'cost_price' => 22.50,
                'selling_price' => 75.00,
                'reorder_level' => 15,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sku' => 'FLT-NEW-NANO',
                ],
            ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'FLT-NEW-NANO',
        ]);
    }

    public function test_device_models_list_includes_bom_stages(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/device-models');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $models = $response->json('data');
        $this->assertNotEmpty($models);

        // Check stages count on the classic model
        $classic = collect($models)->firstWhere('model_code', 'MOD-CLASSIC-6');
        $this->assertNotNull($classic);
        $this->assertCount(6, $classic['stages']);
    }

    public function test_admin_can_create_device_model_with_bom(): void
    {
        $admin = User::where('phone', '0500000001')->first();
        $token = $admin->createToken('test')->plainTextToken;

        $p1 = Product::where('sku', 'FLT-PP-10')->first();
        $p2 = Product::where('sku', 'FLT-GAC-10')->first();
        $p3 = Product::where('sku', 'FLT-RO-MEM-75')->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/device-models', [
                'model_code' => 'MOD-COMPACT-3',
                'name' => 'فلتر مدمج 3 مراحل للمقاهي والمكاتب',
                'stages_count' => 3,
                'stages' => [
                    ['stage_number' => 1, 'stage_name_ar' => 'قطن أولي', 'product_id' => $p1->id, 'default_lifespan_days' => 60],
                    ['stage_number' => 2, 'stage_name_ar' => 'كربون نشط', 'product_id' => $p2->id, 'default_lifespan_days' => 120],
                    ['stage_number' => 3, 'stage_name_ar' => 'ممبرين فائق', 'product_id' => $p3->id, 'default_lifespan_days' => 365],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'model_code' => 'MOD-COMPACT-3',
                ],
            ]);

        $this->assertDatabaseHas('device_models', ['model_code' => 'MOD-COMPACT-3']);
        $this->assertDatabaseHas('device_model_stages', [
            'stage_number' => 3,
            'default_lifespan_days' => 365,
        ]);
    }
}
