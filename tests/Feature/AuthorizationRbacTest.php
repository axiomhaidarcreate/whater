<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unauthenticated_request_to_protected_route_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/test-rbac/technician-only');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'status_code' => 401,
                'error_code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_technician_can_access_technician_only_endpoint(): void
    {
        $tech = User::where('phone', '0500000002')->first(); // Technician
        $token = $tech->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/technician-only');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_customer_cannot_access_technician_endpoint(): void
    {
        $customer = User::where('phone', '0500000004')->first(); // Customer
        $token = $customer->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/technician-only');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status_code' => 403,
                'error_code' => 'FORBIDDEN',
            ]);
    }

    public function test_sales_rep_cannot_access_technician_endpoint(): void
    {
        $sales = User::where('phone', '0500000007')->first(); // Sales Rep
        $token = $sales->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/technician-only');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'status_code' => 403,
                'error_code' => 'FORBIDDEN',
            ]);
    }

    public function test_sales_rep_can_access_sales_endpoint(): void
    {
        $sales = User::where('phone', '0500000007')->first();
        $token = $sales->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/sales-only');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_call_center_can_access_call_center_endpoint(): void
    {
        $callCenter = User::where('phone', '0500000003')->first();
        $token = $callCenter->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/call-center-only');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    public function test_super_admin_bypasses_and_can_access_all_endpoints(): void
    {
        $admin = User::where('phone', '0500000001')->first(); // Super Admin
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/technician-only');

        $response->assertStatus(200);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/test-rbac/sales-only');

        $response->assertStatus(200);
    }

    public function test_accountant_has_required_business_permissions(): void
    {
        $accountant = User::where('phone', '0500000006')->first(); // Accountant
        $this->assertNotNull($accountant);

        // المحاسب: يضيف المشتريات، يسوي فواتير مبيعات، يجرد المخزون، يضيف عملاء
        $this->assertTrue($accountant->hasPermission('procurement.create'), 'Accountant must be able to create procurement');
        $this->assertTrue($accountant->hasPermission('procurement.approve'), 'Accountant must be able to approve procurement');
        $this->assertTrue($accountant->hasPermission('suppliers.manage'), 'Accountant must be able to manage suppliers');
        $this->assertTrue($accountant->hasPermission('sales.create'), 'Accountant must be able to create sales orders');
        $this->assertTrue($accountant->hasPermission('invoices.issue'), 'Accountant must be able to issue invoices');
        $this->assertTrue($accountant->hasPermission('inventory.view'), 'Accountant must be able to view inventory');
        $this->assertTrue($accountant->hasPermission('inventory.adjust'), 'Accountant must be able to adjust inventory (stocktaking)');
        $this->assertTrue($accountant->hasPermission('customers.create'), 'Accountant must be able to add customers');
        $this->assertTrue($accountant->hasPermission('customers.update'), 'Accountant must be able to update customers');
        $this->assertTrue($accountant->hasPermission('accounting.view'), 'Accountant must have accounting.view');
        $this->assertTrue($accountant->hasPermission('accounting.manage'), 'Accountant must have accounting.manage');
    }

    public function test_call_center_has_appointment_assignment_permission(): void
    {
        $callCenter = User::where('phone', '0500000003')->first();
        $this->assertNotNull($callCenter);

        $this->assertTrue($callCenter->hasPermission('customers.view'));
        $this->assertTrue($callCenter->hasPermission('customers.create'));
        $this->assertTrue($callCenter->hasPermission('telesales.view'));
        $this->assertTrue($callCenter->hasPermission('tickets.create'));
        $this->assertTrue($callCenter->hasPermission('tickets.assign'), 'Call center must be able to assign appointments to technicians');
    }
}
