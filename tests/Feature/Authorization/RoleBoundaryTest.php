<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendedor_cannot_enter_inventory_or_manage_users(): void
    {
        $vendedor = User::factory()->create(['role' => 'vendedor']);

        $this->actingAs($vendedor)->get('/inventario')->assertForbidden();
        $this->actingAs($vendedor)->get('/admin/usuarios')->assertForbidden();
    }

    public function test_bodeguero_cannot_enter_pos_or_cash(): void
    {
        $bodeguero = User::factory()->create(['role' => 'bodeguero']);

        $this->actingAs($bodeguero)->get('/ventas')->assertForbidden();
        $this->actingAs($bodeguero)->get('/caja')->assertForbidden();
    }

    public function test_admin_cannot_create_another_administrator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Segundo Admin',
            'email' => 'segundo-admin@machima.test',
            'password' => 'PasswordSegura123!',
            'password_confirmation' => 'PasswordSegura123!',
            'role' => 'admin',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'segundo-admin@machima.test']);
    }

    public function test_admin_can_create_vendedor_and_bodeguero(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Equipo Venta',
            'email' => 'nuevo-vendedor@machima.test',
            'password' => 'PasswordSegura123!',
            'password_confirmation' => 'PasswordSegura123!',
            'role' => 'vendedor',
        ])->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo-vendedor@machima.test',
            'role' => 'vendedor',
        ]);
    }

    public function test_role_descriptions_are_integrated_into_user_management(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Roles y responsabilidades')
            ->assertSee('Bodeguero')
            ->assertSee('Vendedor');

        $this->get(route('admin.roles.index'))
            ->assertRedirect(route('admin.usuarios.index').'#roles');
    }
}
