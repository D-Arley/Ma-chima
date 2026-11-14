<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    public function test_admin_can_access_every_role_gate(): void
    {
        $admin = $this->userWithRole('admin');

        foreach ($this->abilities() as $ability) {
            $this->assertTrue(Gate::forUser($admin)->allows($ability));
        }
    }

    public function test_vendedor_can_manage_sales_count_and_receive_stock(): void
    {
        $vendedor = $this->userWithRole('vendedor');
        $gate = Gate::forUser($vendedor);

        $this->assertTrue($gate->allows('manage-sales'));
        $this->assertTrue($gate->allows('count-inventory'));
        $this->assertTrue($gate->allows('receive-stock'));
        $this->assertFalse($gate->allows('manage-inventory'));
        $this->assertFalse($gate->allows('rename-products'));
        $this->assertFalse($gate->allows('deliver-stock'));
        $this->assertFalse($gate->allows('admin-access'));
    }

    public function test_bodeguero_can_manage_and_deliver_stock_but_not_sales(): void
    {
        $bodeguero = $this->userWithRole('bodeguero');
        $gate = Gate::forUser($bodeguero);

        $this->assertTrue($gate->allows('count-inventory'));
        $this->assertTrue($gate->allows('manage-inventory'));
        $this->assertTrue($gate->allows('rename-products'));
        $this->assertTrue($gate->allows('deliver-stock'));
        $this->assertFalse($gate->allows('manage-sales'));
        $this->assertFalse($gate->allows('receive-stock'));
        $this->assertFalse($gate->allows('admin-access'));
    }

    /**
     * @return list<string>
     */
    private function abilities(): array
    {
        return [
            'admin-access',
            'manage-sales',
            'count-inventory',
            'receive-stock',
            'manage-inventory',
            'rename-products',
            'deliver-stock',
        ];
    }

    private function userWithRole(string $role): User
    {
        $user = new User;
        $user->role = $role;

        return $user;
    }
}
