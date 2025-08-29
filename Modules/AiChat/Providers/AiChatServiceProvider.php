<?php

namespace Modules\AiChat\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AiChatServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'AiChat';

    protected string $moduleNameLower = 'aichat';

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
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerMenu();
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
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php')], 'config');
        $this->mergeConfigFrom(module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);

        $componentNamespace = str_replace('/', '\\', config('modules.namespace').'\\'.$this->moduleName.'\\'.ltrim(config('modules.paths.generator.component-class.path'), config('modules.paths.app_folder', '')));
        Blade::componentNamespace($componentNamespace, $this->moduleNameLower);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    /**
     * Register module menu.
     */
    private function registerMenu(): void
    {
        // Register the module's menu
        $menuPath = module_path($this->moduleName, 'resources/menu/verticalMenu.json');
        if (file_exists($menuPath)) {
            $this->loadMenu($menuPath);
        }
    }

    /**
     * Load menu items from the given file.
     *
     * @param string $path
     */
    private function loadMenu(string $path): void
    {
        $mainMenuPath = base_path('resources/menu/verticalMenu.json');

        if (!file_exists($mainMenuPath)) {
            return;
        }

        // Read the module menu
        $moduleMenuJson = file_get_contents($path);
        $moduleMenu = json_decode($moduleMenuJson, true);

        if (!isset($moduleMenu['menu']) || !is_array($moduleMenu['menu'])) {
            return;
        }

        // Read the main menu
        $mainMenuJson = file_get_contents($mainMenuPath);
        $mainMenu = json_decode($mainMenuJson, true);

        if (!isset($mainMenu['menu']) || !is_array($mainMenu['menu'])) {
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

            if (!$exists) {
                $mainMenu['menu'][] = $menuItem;
            }
        }

        // Save the updated main menu
        file_put_contents($mainMenuPath, json_encode($mainMenu, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
