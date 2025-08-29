<?php

namespace Modules\FileManagerCore\Providers;

use App\Services\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\FileManagerCore\app\Settings\FileManagerCoreSettings;
use Modules\FileManagerCore\Contracts\FileManagerInterface;
use Modules\FileManagerCore\Services\FileManagerService;
use Modules\FileManagerCore\Services\FileManagerSettingsService;
use Modules\FileManagerCore\Services\FileSecurityService;
use Modules\FileManagerCore\Services\FileValidationService;
use Modules\FileManagerCore\Services\StorageDriverManager;
use Modules\FileManagerCore\Services\ThumbnailService;

class FileManagerCoreServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'FileManagerCore';

    /**
     * @var string
     */
    protected $moduleNameLower = 'filemanagercore';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));

        // Register module settings
        $this->registerModuleSettings();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Register core services
        $this->registerCoreServices();

        // Register bindings
        $this->registerBindings();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([
            $sourcePath => $viewPath,
        ], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'resources/lang'), $this->moduleNameLower);
            $this->loadJsonTranslationsFrom(module_path($this->moduleName, 'resources/lang'));
        }
    }

    /**
     * Register core services.
     *
     * @return void
     */
    protected function registerCoreServices()
    {
        // Register Settings Service first (needed by other services)
        $this->app->singleton(FileManagerSettingsService::class, function ($app) {
            return new FileManagerSettingsService(
                $app->make(\App\Services\Settings\ModuleSettingsService::class)
            );
        });

        // Register Storage Driver Manager as singleton
        $this->app->singleton(StorageDriverManager::class, function ($app) {
            return new StorageDriverManager;
        });

        // Register File Validation Service
        $this->app->singleton(FileValidationService::class, function ($app) {
            return new FileValidationService(
                $app->make(FileManagerSettingsService::class)
            );
        });

        // Register Thumbnail Service
        $this->app->singleton(ThumbnailService::class, function ($app) {
            return new ThumbnailService(
                $app->make(FileManagerSettingsService::class)
            );
        });

        // Register File Security Service
        $this->app->singleton(FileSecurityService::class, function ($app) {
            return new FileSecurityService;
        });

        // Register File Manager Service
        $this->app->singleton(FileManagerService::class, function ($app) {
            return new FileManagerService(
                $app->make(StorageDriverManager::class),
                $app->make(FileValidationService::class),
                $app->make(FileManagerSettingsService::class)
            );
        });
    }

    /**
     * Register service bindings.
     *
     * @return void
     */
    protected function registerBindings()
    {
        // Bind FileManagerInterface to FileManagerService
        $this->app->bind(FileManagerInterface::class, FileManagerService::class);

        // Register settings services
        $this->app->singleton('filemanagercore.settings', function () {
            return new FileManagerCoreSettings;
        });
    }

    /**
     * Register module settings with the registry
     */
    protected function registerModuleSettings(): void
    {
        if ($this->app->bound(SettingsRegistry::class)) {
            $registry = $this->app->make(SettingsRegistry::class);

            $registry->registerModule('filemanagercore', [
                'name' => __('File Management'),
                'description' => __('Configure file upload limits, storage quotas, thumbnails, security, and cleanup settings'),
                'icon' => 'bx bx-file',
                'handler' => FileManagerCoreSettings::class,
                'permissions' => [],
                'order' => 15,
            ]);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            FileManagerInterface::class,
            FileManagerService::class,
            StorageDriverManager::class,
            FileValidationService::class,
            ThumbnailService::class,
            FileSecurityService::class,
        ];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (Config::get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }
}
