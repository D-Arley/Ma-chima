<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_recovery_page_explains_the_temporary_password_process_without_requesting_email(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Solicita una clave temporal')
            ->assertSee('Comunícate con el administrador')
            ->assertDontSee('name="email"', false);
    }
}
