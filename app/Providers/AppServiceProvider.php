<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
            if ($src !== null) {
                return [
                    'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' :
                      (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : ''),
                ];
            }

            return [];
        });

        /**
         * Register Custom Migration Paths
         */
        $this->loadMigrationsFrom([
            database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'tenant',
        ]);

        /**
         * Register custom authorization gates
         */
        $this->registerGates();
    }

    /**
     * Register custom gates
     */
    protected function registerGates(): void
    {
        // Define 'any' gate - checks if user has ANY of the given permissions
        Gate::define('any', function ($user, $permissions) {
            // Handle both string and array inputs
            $permissions = is_array($permissions) ? $permissions : [$permissions];

            foreach ($permissions as $permission) {
                if ($user->hasPermissionTo($permission)) {
                    return true;
                }
            }

            return false;
        });
    }
}
