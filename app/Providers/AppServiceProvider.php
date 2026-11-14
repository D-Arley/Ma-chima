<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->isAdmin() ? true : null);

        Gate::define('admin-access', fn (User $user) => $user->isAdmin());
        Gate::define('manage-sales', fn (User $user) => $user->hasRole('vendedor'));
        Gate::define('count-inventory', fn (User $user) => $user->hasRole('vendedor', 'bodeguero'));
        Gate::define('receive-stock', fn (User $user) => $user->hasRole('vendedor'));
        Gate::define('manage-inventory', fn (User $user) => $user->hasRole('bodeguero'));
        Gate::define('rename-products', fn (User $user) => $user->hasRole('bodeguero'));
        Gate::define('deliver-stock', fn (User $user) => $user->hasRole('bodeguero'));
    }
}
