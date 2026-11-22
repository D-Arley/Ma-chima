<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordForceChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_generated_temporary_password_requires_a_strong_password_change_before_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $vendedor = User::factory()->create(['role' => 'vendedor']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.usuarios.temporary-password', $vendedor))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('temporary_password');

        $temporaryPassword = $response->getSession()->get('temporary_password');
        $vendedor->refresh();

        $this->assertTrue(Hash::check($temporaryPassword, $vendedor->password));
        $this->assertTrue($vendedor->must_change_password);

        $this->actingAs($vendedor)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.force-change'));

        $this->get(route('password.force-change'))
            ->assertOk()
            ->assertSee('Al menos 12 caracteres')
            ->assertSee('Al menos un símbolo');

        $this->put(route('password.force-change.update'), [
            'password' => 'ContrasenaSegura123',
            'password_confirmation' => 'ContrasenaSegura123',
        ])->assertSessionHasErrors('password');

        $this->put(route('password.force-change.update'), [
            'password' => 'ContrasenaSegura123!',
            'password_confirmation' => 'ContrasenaSegura123!',
        ])->assertRedirect(route('dashboard'));

        $vendedor->refresh();

        $this->assertFalse($vendedor->must_change_password);
        $this->assertTrue(Hash::check('ContrasenaSegura123!', $vendedor->password));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_regular_account_cannot_open_the_forced_password_change_page(): void
    {
        $vendedor = User::factory()->create(['role' => 'vendedor']);

        $this->actingAs($vendedor)
            ->get(route('password.force-change'))
            ->assertForbidden();
    }
}
