<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Database\Seeders\InitialUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_account_for_each_role_with_hashed_passwords(): void
    {
        config()->set('machima.initial_users', [
            [
                'name' => 'Administrador',
                'email' => 'admin@machima.test',
                'role' => 'admin',
                'password' => 'password-admin',
            ],
            [
                'name' => 'Vendedor',
                'email' => 'vendedor@machima.test',
                'role' => 'vendedor',
                'password' => 'password-vendedor',
            ],
            [
                'name' => 'Bodeguero',
                'email' => 'bodeguero@machima.test',
                'role' => 'bodeguero',
                'password' => 'password-bodeguero',
            ],
        ]);

        $this->seed(InitialUsersSeeder::class);

        $this->assertSame(
            ['admin', 'bodeguero', 'vendedor'],
            User::query()->orderBy('role')->pluck('role')->all()
        );
        $this->assertNotSame('password-admin', User::query()->where('role', 'admin')->value('password'));
        $this->assertNotNull(User::query()->where('role', 'admin')->value('email_verified_at'));
    }

    public function test_it_updates_existing_demo_user_passwords_from_config(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@machima.test',
            'role' => 'admin',
            'password' => bcrypt('password-anterior'),
        ]);

        config()->set('machima.initial_users', [
            [
                'name' => 'Administrador',
                'email' => 'admin@machima.test',
                'role' => 'admin',
                'password' => 'password-nueva-admin',
            ],
        ]);

        $this->seed(InitialUsersSeeder::class);

        $user->refresh();
        $this->assertTrue(password_verify('password-nueva-admin', $user->password));
        $this->assertFalse(password_verify('password-anterior', $user->password));
    }
}
