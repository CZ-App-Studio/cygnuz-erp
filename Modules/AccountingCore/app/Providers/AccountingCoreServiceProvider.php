<?php

namespace Modules\AccountingCore\app\Providers;

use App\Services\AddonService\AddonService;
use App\Services\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\AccountingCore\app\Settings\AccountingCoreSettings;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AccountingCoreServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'AccountingCore';

    protected string $nameLower = 'accountingcore';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerMenu();

        // Register module settings
        $this->registerModuleSettings();

        // Check if AccountingPro is enabled
        $this->checkAccountingProStatus();
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $relativeConfigPath = config('modules.paths.generator.config.path');
        $configPath = module_path($this->name, $relativeConfigPath);

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $relativePath = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $configKey = $this->nameLower.'.'.str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $relativePath);
                    $key = ($relativePath === 'config.php') ? $this->nameLower : $configKey;

                    $this->publishes([$file->getPathname() => config_path($relativePath)], 'config');
                    $this->mergeConfigFrom($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        $componentNamespace = $this->module_namespace($this->name, $this->app_path(config('modules.paths.generator.component-class.path')));
        Blade::componentNamespace($componentNamespace, $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }

    /**
     * Register module settings with the registry
     */
    protected function registerModuleSettings(): void
    {
        if ($this->app->bound(SettingsRegistry::class)) {
            $registry = $this->app->make(SettingsRegistry::class);

            $registry->registerModule('accountingcore', [
                'name' => __('Accounting'),
                'description' => __('Configure basic accounting settings for income and expense tracking'),
                'icon' => 'bx bx-calculator',
                'handler' => AccountingCoreSettings::class,
                'permissions' => [],
                'order' => 10,
            ]);
        }
    }

    /**
     * Check if AccountingPro is enabled and bind appropriate services
     */
    protected function checkAccountingProStatus(): void
    {
        $addonService = app(AddonService::class);

        if ($addonService->isAddonEnabled('AccountingPro')) {
            // AccountingPro is enabled, bind it as the primary accounting provider
            $this->app->bind('accounting.provider', 'Modules\AccountingPro\app\Services\AccountingProService');
        } else {
            // Use basic accounting
            $this->app->bind('accounting.provider', 'Modules\AccountingCore\app\Services\AccountingCoreService');
        }
    }

    /**
     * Register module menu.
     */
    private function registerMenu(): void
    {
        // Register the module's menu
        $menuPath = module_path($this->name, 'resources/menu/verticalMenu.json');
        if (file_exists($menuPath)) {
            $this->loadMenu($menuPath);
        }
    }

    /**
     * Load menu items from the given file.
     */
    private function loadMenu(string $path): void
    {
        $mainMenuPath = base_path('resources/menu/verticalMenu.json');

        if (! file_exists($mainMenuPath)) {
            return;
        }

        // Read the module menu
        $moduleMenuJson = file_get_contents($path);
        $moduleMenu = json_decode($moduleMenuJson, true);

        if (! isset($moduleMenu['menu']) || ! is_array($moduleMenu['menu'])) {
            return;
        }

        // Read the main menu
        $mainMenuJson = file_get_contents($mainMenuPath);
        $mainMenu = json_decode($mainMenuJson, true);

        if (! isset($mainMenu['menu']) || ! is_array($mainMenu['menu'])) {
            return;
        }

        // Append module menu items to the main menu
        foreach ($moduleMenu['menu'] as $menuItem) {
            // Avoid duplicate entries
            $exists = false;
            foreach ($mainMenu['menu'] as $existingItem) {
                if (isset($menuItem['name'], $existingItem['name']) && $menuItem['name'] === $existingItem['name']) {
                    $exists = true;
                    break;
                }

                if (isset($menuItem['menuHeader'], $existingItem['menuHeader']) && $menuItem['menuHeader'] === $existingItem['menuHeader']) {
                    $exists = true;
                    break;
                }
            }

            if (! $exists) {
                $mainMenu['menu'][] = $menuItem;
            }
        }

        // Save the updated main menu
        file_put_contents($mainMenuPath, json_encode($mainMenu, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
