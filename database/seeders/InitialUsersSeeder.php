<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('machima.initial_users') as $account) {
            $password = $account['password'];

            if (! is_string($password) || strlen($password) < 12) {
                throw new RuntimeException(
                    "Define una contraseña de al menos 12 caracteres para {$account['role']} en el archivo .env."
                );
            }

            $user = User::firstOrNew(['email' => $account['email']]);
            $isNewUser = ! $user->exists;

            $user->name = $account['name'];
            $user->role = $account['role'];
            $user->email_verified_at ??= now();

            if ($isNewUser) {
                $user->password = Hash::make($password);
            }

            $user->save();
        }
    }
}
