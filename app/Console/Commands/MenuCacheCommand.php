<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Menu\MenuAggregator;
use App\Services\Menu\MenuRegistry;
use Illuminate\Support\Facades\Cache;

class MenuCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'menu:cache 
                            {action=refresh : Action to perform (refresh, clear, status, search)}
                            {--module= : Specific module to manage}
                            {--search= : Search term for menu items}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage menu cache - refresh, clear, status, or search';

    protected MenuAggregator $menuAggregator;
    protected MenuRegistry $menuRegistry;

    /**
     * Create a new command instance.
     */
    public function __construct(MenuAggregator $menuAggregator, MenuRegistry $menuRegistry)
    {
        parent::__construct();
        $this->menuAggregator = $menuAggregator;
        $this->menuRegistry = $menuRegistry;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'refresh' => $this->refreshCache(),
            'clear' => $this->clearCache(),
            'status' => $this->showStatus(),
            'search' => $this->searchMenu(),
            default => $this->error("Unknown action: {$action}") ?? 1,
        };
    }

    /**
     * Refresh menu cache
     */
    protected function refreshCache(): int
    {
        $this->info('Refreshing menu cache...');

        // Clear existing cache
        $this->menuAggregator->clearCache();

        // Force refresh for both menu types
        $verticalMenu = $this->menuAggregator->getMenu('vertical', true);
        $horizontalMenu = $this->menuAggregator->getMenu('horizontal', true);

        $this->info('✓ Menu cache refreshed successfully');
        
        // Show statistics
        $verticalCount = count($verticalMenu['menu'] ?? []);
        $horizontalCount = count($horizontalMenu['menu'] ?? []);
        
        $this->table(
            ['Menu Type', 'Items Count'],
            [
                ['Vertical Menu', $verticalCount],
                ['Horizontal Menu', $horizontalCount],
            ]
        );

        return 0;
    }

    /**
     * Clear menu cache
     */
    protected function clearCache(): int
    {
        $this->info('Clearing menu cache...');
        
        $this->menuAggregator->clearCache();
        
        // Also clear any database cached menus if using database
        Cache::tags(['menus'])->flush();
        
        $this->info('✓ Menu cache cleared successfully');
        
        return 0;
    }

    /**
     * Show menu cache status
     */
    protected function showStatus(): int
    {
        $this->info('Menu System Status');
        $this->info('==================');

        // Check cache status
        $verticalCached = Cache::has('menu.aggregated.vertical');
        $horizontalCached = Cache::has('menu.aggregated.horizontal');

        $this->table(
            ['Cache Key', 'Status', 'TTL (seconds)'],
            [
                ['Vertical Menu', $verticalCached ? '✓ Cached' : '✗ Not Cached', $verticalCached ? Cache::ttl('menu.aggregated.vertical') : 'N/A'],
                ['Horizontal Menu', $horizontalCached ? '✓ Cached' : '✗ Not Cached', $horizontalCached ? Cache::ttl('menu.aggregated.horizontal') : 'N/A'],
            ]
        );

        // Show module menus
        if ($module = $this->option('module')) {
            $this->showModuleMenu($module);
        } else {
            $this->showAllModules();
        }

        return 0;
    }

    /**
     * Search menu items
     */
    protected function searchMenu(): int
    {
        $searchTerm = $this->option('search');
        
        if (!$searchTerm) {
            $searchTerm = $this->ask('Enter search term');
        }

        $this->info("Searching for: {$searchTerm}");
        
        $results = $this->menuAggregator->searchMenu($searchTerm);
        
        if ($results->isEmpty()) {
            $this->warn('No menu items found matching your search.');
            return 0;
        }

        $this->info("Found {$results->count()} menu item(s):");
        
        $tableData = $results->map(function ($item) {
            $slug = isset($item['slug']) ? (is_array($item['slug']) ? $item['slug'][0] : $item['slug']) : 'N/A';
            return [
                $item['name'] ?? 'N/A',
                $item['url'] ?? 'N/A',
                $item['module'] ?? $item['addon'] ?? 'Core',
                $slug
            ];
        })->toArray();

        $this->table(
            ['Name', 'URL', 'Module', 'Slug'],
            $tableData
        );

        return 0;
    }

    /**
     * Show menu for specific module
     */
    protected function showModuleMenu(string $module): void
    {
        $this->info("\nModule: {$module}");
        $this->info('-------------------');

        $moduleMenu = $this->menuAggregator->getModuleMenu($module);
        
        if (empty($moduleMenu)) {
            $this->warn("No menu items found for module: {$module}");
            return;
        }

        foreach ($moduleMenu as $item) {
            if (isset($item['menuHeader'])) {
                $this->line("  Header: {$item['menuHeader']}");
            } else {
                $url = isset($item['url']) ? $item['url'] : 'no-url';
                $this->line("  - {$item['name']} ({$url})");
                if (isset($item['submenu'])) {
                    foreach ($item['submenu'] as $subItem) {
                        $subUrl = isset($subItem['url']) ? $subItem['url'] : 'no-url';
                        $this->line("    • {$subItem['name']} ({$subUrl})");
                    }
                }
            }
        }
    }

    /**
     * Show all modules with menus
     */
    protected function showAllModules(): void
    {
        $this->info("\nModules with Menu Items:");
        $this->info('------------------------');

        $modulesPath = base_path('Modules');
        $modules = [];

        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }
                
                $menuPath = "{$modulesPath}/{$module}/resources/menu/verticalMenu.json";
                if (file_exists($menuPath)) {
                    $modules[] = [
                        $module,
                        file_exists($menuPath) ? '✓' : '✗',
                        $this->getModuleMenuCount($module)
                    ];
                }
            }
        }

        if (empty($modules)) {
            $this->warn('No modules with menu items found.');
            return;
        }

        $this->table(
            ['Module', 'Has Menu', 'Items Count'],
            $modules
        );
    }

    /**
     * Get menu item count for a module
     */
    protected function getModuleMenuCount(string $module): int
    {
        $moduleMenu = $this->menuAggregator->getModuleMenu($module);
        return count(array_filter($moduleMenu, function ($item) {
            return !isset($item['menuHeader']);
        }));
    }
}