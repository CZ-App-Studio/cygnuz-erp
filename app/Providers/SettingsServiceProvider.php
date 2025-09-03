<?php

namespace App\Providers;

use App\Services\Settings\ModuleSettingsService;
use App\Services\Settings\SettingsCacheManager;
use App\Services\Settings\SettingsRegistry;
use App\Services\Settings\SettingsService;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register settings services as singletons
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(ModuleSettingsService::class);
        $this->app->singleton(SettingsRegistry::class);
        $this->app->singleton(SettingsCacheManager::class);

        // Register aliases for easier access
        $this->app->alias(SettingsService::class, 'settings');
        $this->app->alias(ModuleSettingsService::class, 'module.settings');
        $this->app->alias(SettingsRegistry::class, 'settings.registry');
        $this->app->alias(SettingsCacheManager::class, 'settings.cache');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register settings metadata
        $this->registerSettingsMetadata();
    }

    /**
     * Register settings metadata for UI generation
     */
    protected function registerSettingsMetadata(): void
    {
        $registry = $this->app->make(SettingsRegistry::class);

        // Register general settings metadata
        $registry->registerMultiple([
            'app_name' => [
                'category' => 'general',
                'label' => 'Application Name',
                'type' => 'string',
                'validation' => ['required', 'string', 'max:255'],
                'help' => 'The name of your application',
            ],
            'timezone' => [
                'category' => 'regional',
                'label' => 'Default Timezone',
                'type' => 'select',
                'options' => 'timezones',
                'validation' => ['required', 'timezone'],
                'help' => 'Default timezone for the application',
            ],
            'currency' => [
                'category' => 'regional',
                'label' => 'Currency Code',
                'type' => 'string',
                'validation' => ['required', 'string', 'size:3'],
                'help' => 'Three-letter currency code (e.g., USD, EUR)',
            ],
            'currency_symbol' => [
                'category' => 'regional',
                'label' => 'Currency Symbol',
                'type' => 'string',
                'validation' => ['required', 'string', 'max:5'],
                'help' => 'Currency symbol to display',
            ],
            // Add more metadata as needed
        ]);
    }
}
