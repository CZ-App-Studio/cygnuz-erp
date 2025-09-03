<?php

namespace App\Services\Menu;

use App\Services\AddonService\IAddonService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class MenuAggregator
{
    protected IAddonService $addonService;

    protected array $menuCache = [];

    protected int $cacheTime = 3600; // 1 hour cache

    public function __construct(IAddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Get aggregated menu from all sources
     */
    public function getMenu(string $menuType = 'vertical', bool $forceRefresh = false): array
    {
        $cacheKey = "menu.aggregated.{$menuType}";

        if (! $forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $aggregatedMenu = $this->aggregateMenus($menuType);
        Cache::put($cacheKey, $aggregatedMenu, $this->cacheTime);

        return $aggregatedMenu;
    }

    /**
     * Aggregate menus from all modules
     */
    protected function aggregateMenus(string $menuType): array
    {
        $menus = collect();

        // 1. Load core menu (system essentials only)
        $coreMenu = $this->loadCoreMenu($menuType);
        if ($coreMenu) {
            $menus = $menus->merge($coreMenu);
        }

        // 2. Load module menus
        $moduleMenus = $this->loadModuleMenus($menuType);
        foreach ($moduleMenus as $moduleMenu) {
            $menus = $menus->merge($moduleMenu);
        }

        // 3. Sort and organize menus
        $organized = $this->organizeMenus($menus);

        return ['menu' => $organized->toArray()];
    }

    /**
     * Load core system menu (minimal essentials)
     */
    protected function loadCoreMenu(string $menuType): ?Collection
    {
        $corePath = base_path("resources/menu/core/{$menuType}Menu.json");

        // If core menu doesn't exist, use existing main menu but filter to essentials
        if (! File::exists($corePath)) {
            $mainPath = base_path("resources/menu/{$menuType}Menu.json");
            if (File::exists($mainPath)) {
                $content = json_decode(File::get($mainPath), true);
                if (isset($content['menu'])) {
                    // Filter to only essential system items
                    return collect($content['menu'])->filter(function ($item) {
                        // Keep headers and core system items
                        if (isset($item['menuHeader'])) {
                            return in_array($item['menuHeader'], [
                                'Dashboard',
                                'System Management',
                            ]);
                        }

                        // Keep core system items without addon
                        return ! isset($item['addon']) && in_array($item['slug'] ?? '', [
                            'dashboard',
                            'users.index',
                            'roles.index',
                            'permissions.index',
                            'settings.index',
                        ]);
                    });
                }
            }
        } else {
            $content = json_decode(File::get($corePath), true);
            if (isset($content['menu'])) {
                return collect($content['menu']);
            }
        }

        return null;
    }

    /**
     * Load menus from all enabled modules
     */
    protected function loadModuleMenus(string $menuType): array
    {
        $moduleMenus = [];
        $modulesPath = base_path('Modules');

        if (! File::isDirectory($modulesPath)) {
            return $moduleMenus;
        }

        $modules = File::directories($modulesPath);

        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);

            // Check if module is enabled
            if (! $this->addonService->isAddonEnabled($moduleName)) {
                continue;
            }

            // Check for module menu file
            $menuPath = "{$modulePath}/resources/menu/{$menuType}Menu.json";
            if (File::exists($menuPath)) {
                $content = json_decode(File::get($menuPath), true);
                if (isset($content['menu'])) {
                    // Add module context to each menu item
                    $moduleMenu = collect($content['menu'])->map(function ($item) use ($moduleName) {
                        if (! isset($item['menuHeader'])) {
                            $item['module'] = $moduleName;
                            // Ensure addon field is set for module items
                            if (! isset($item['addon'])) {
                                $item['addon'] = $moduleName;
                            }
                        }

                        return $item;
                    });
                    $moduleMenus[] = $moduleMenu;
                }
            }
        }

        return $moduleMenus;
    }

    /**
     * Organize and sort menu items
     */
    protected function organizeMenus(Collection $menus): Collection
    {
        $organized = collect();

        // Group by headers and priority
        $currentHeader = null;
        $headerGroups = [];

        foreach ($menus as $item) {
            if (isset($item['menuHeader'])) {
                $currentHeader = $item['menuHeader'];
                if (! isset($headerGroups[$currentHeader])) {
                    $headerGroups[$currentHeader] = [
                        'header' => $item,
                        'items' => collect(),
                        'priority' => $this->getHeaderPriority($currentHeader),
                    ];
                }
            } else {
                if ($currentHeader) {
                    $headerGroups[$currentHeader]['items']->push($item);
                } else {
                    // Items without header go to default group
                    if (! isset($headerGroups['_default'])) {
                        $headerGroups['_default'] = [
                            'header' => null,
                            'items' => collect(),
                            'priority' => 0,
                        ];
                    }
                    $headerGroups['_default']['items']->push($item);
                }
            }
        }

        // Sort header groups by priority
        $sortedGroups = collect($headerGroups)->sortBy('priority');

        // Build final menu structure
        foreach ($sortedGroups as $group) {
            if ($group['header']) {
                $organized->push($group['header']);
            }

            // Sort items within group
            $sortedItems = $this->sortMenuItems($group['items']);
            foreach ($sortedItems as $item) {
                $organized->push($item);
            }
        }

        return $organized;
    }

    /**
     * Get header priority for sorting
     */
    protected function getHeaderPriority(string $header): int
    {
        $priorities = [
            'Dashboard' => 1,
            'Business Operations' => 2,
            'Finance & Accounting' => 3,
            'Inventory & Sales' => 4,
            'Human Resources' => 5,
            'Organization & Documents' => 6,
            'System Management' => 7,
            'Tools & Extensions' => 8,
            'Multi-tenancy (Super Admin Only)' => 9,
            'Inventory & WMS' => 10,
        ];

        return $priorities[$header] ?? 99;
    }

    /**
     * Sort menu items within a group
     */
    protected function sortMenuItems(Collection $items): Collection
    {
        return $items->sortBy(function ($item) {
            // Priority can be set in menu item
            return $item['priority'] ?? 99;
        });
    }

    /**
     * Clear menu cache
     */
    public function clearCache(): void
    {
        Cache::forget('menu.aggregated.vertical');
        Cache::forget('menu.aggregated.horizontal');
    }

    /**
     * Register a dynamic menu item at runtime
     */
    public function registerMenuItem(array $menuItem, string $menuType = 'vertical'): void
    {
        // Clear cache when new item is registered
        $this->clearCache();
        // This would be used by modules to register menus programmatically
        // The actual registration would happen through MenuRegistry
    }

    /**
     * Search menu items
     */
    public function searchMenu(string $query, string $menuType = 'vertical'): Collection
    {
        $menu = $this->getMenu($menuType);
        $results = collect();

        if (! isset($menu['menu'])) {
            return $results;
        }

        foreach ($menu['menu'] as $item) {
            if (isset($item['menuHeader'])) {
                continue;
            }

            // Search in menu name
            if (isset($item['name']) && stripos($item['name'], $query) !== false) {
                $results->push($item);
            }

            // Search in submenu items
            if (isset($item['submenu'])) {
                foreach ($item['submenu'] as $subItem) {
                    if (isset($subItem['name']) && stripos($subItem['name'], $query) !== false) {
                        $results->push($subItem);
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Get menu items for a specific module
     */
    public function getModuleMenu(string $moduleName, string $menuType = 'vertical'): array
    {
        $menu = $this->getMenu($menuType);
        $moduleItems = [];

        if (! isset($menu['menu'])) {
            return $moduleItems;
        }

        foreach ($menu['menu'] as $item) {
            if (isset($item['module']) && $item['module'] === $moduleName) {
                $moduleItems[] = $item;
            } elseif (isset($item['addon']) && $item['addon'] === $moduleName) {
                $moduleItems[] = $item;
            }
        }

        return $moduleItems;
    }
}
