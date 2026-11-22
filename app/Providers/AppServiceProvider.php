<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
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
        // URLs de recursos en español: /crear y /editar.
        Route::resourceVerbs(['create' => 'crear', 'edit' => 'editar']);

        // Usuarios desactivados no tienen ningún permiso; el Super Administrador los tiene todos.
        Gate::before(function (User $user) {
            if (! $user->is_active) {
                return false;
            }

            return $user->isSuperAdmin() ? true : null;
        });

        // Una gate por permiso del catálogo: `can:settings.manage`, `@can('zones.manage')`...
        foreach (array_keys(config('permissions.permissions')) as $key) {
            Gate::define($key, fn (User $user) => $user->hasPermission($key));
        }
    }
}
