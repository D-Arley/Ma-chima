<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_administration_responsibilities(): void
    {
        $this->signInWithRole('admin');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administrador')
            ->assertSee('Administración general')
            ->assertSee('Equipo y alertas');
    }

    public function test_vendedor_sees_sales_and_stock_responsibilities(): void
    {
        $this->signInWithRole('vendedor');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administrar ventas')
            ->assertSee('Recibir mercancía');
    }

    public function test_bodeguero_sees_warehouse_responsibilities(): void
    {
        $this->signInWithRole('bodeguero');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ingresar mercancía')
            ->assertSee('Contar y entregar');
    }

    private function signInWithRole(string $role): void
    {
        $user = User::factory()->create();
        $user->role = $role;
        $user->save();

        $this->actingAs($user);
    }
}
