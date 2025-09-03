<?php

namespace App\Services\Menu;

class MenuRegistry
{
    protected static array $registeredMenus = [];

    protected static array $menuPermissions = [];

    protected static array $menuCallbacks = [];

    /**
     * Register menu items for a module
     */
    public function register(string $module, array $config): void
    {
        self::$registeredMenus[$module] = $config;

        // Store permissions if provided
        if (isset($config['permissions'])) {
            self::$menuPermissions[$module] = $config['permissions'];
        }
    }

    /**
     * Register a callback to build menu dynamically
     */
    public function registerCallback(string $module, callable $callback): void
    {
        self::$menuCallbacks[$module] = $callback;
    }

    /**
     * Get all registered menus
     */
    public function getRegisteredMenus(): array
    {
        $menus = self::$registeredMenus;

        // Execute callbacks to get dynamic menus
        foreach (self::$menuCallbacks as $module => $callback) {
            $menus[$module] = call_user_func($callback);
        }

        return $menus;
    }

    /**
     * Get menu for specific module
     */
    public function getModuleMenu(string $module): ?array
    {
        if (isset(self::$menuCallbacks[$module])) {
            return call_user_func(self::$menuCallbacks[$module]);
        }

        return self::$registeredMenus[$module] ?? null;
    }

    /**
     * Get menu permissions for a module
     */
    public function getModulePermissions(string $module): array
    {
        return self::$menuPermissions[$module] ?? [];
    }

    /**
     * Get all menu permissions
     */
    public function getAllPermissions(): array
    {
        $permissions = [];
        foreach (self::$menuPermissions as $module => $modulePermissions) {
            $permissions = array_merge($permissions, $modulePermissions);
        }

        return $permissions;
    }

    /**
     * Clear all registrations
     */
    public function clear(): void
    {
        self::$registeredMenus = [];
        self::$menuPermissions = [];
        self::$menuCallbacks = [];
    }

    /**
     * Check if a module has registered menus
     */
    public function hasModule(string $module): bool
    {
        return isset(self::$registeredMenus[$module]) || isset(self::$menuCallbacks[$module]);
    }

    /**
     * Remove a module's menu registration
     */
    public function unregister(string $module): void
    {
        unset(self::$registeredMenus[$module]);
        unset(self::$menuPermissions[$module]);
        unset(self::$menuCallbacks[$module]);
    }

    /**
     * Build menu structure from registration
     */
    public function buildMenuStructure(array $config): array
    {
        $menuItem = [
            'name' => $config['name'],
            'icon' => $config['icon'] ?? 'menu-icon bx bx-circle',
            'slug' => $config['slug'] ?? null,
            'url' => $config['url'] ?? null,
            'addon' => $config['addon'] ?? null,
            'module' => $config['module'] ?? null,
            'priority' => $config['priority'] ?? 99,
            'permission' => $config['permission'] ?? null,
            'role' => $config['role'] ?? null,
        ];

        // Handle submenu
        if (isset($config['submenu']) && is_array($config['submenu'])) {
            $menuItem['submenu'] = array_map(function ($subItem) {
                return $this->buildMenuStructure($subItem);
            }, $config['submenu']);
        }

        // Handle badges
        if (isset($config['badge'])) {
            $menuItem['badge'] = $config['badge'];
        }

        // Remove null values
        return array_filter($menuItem, function ($value) {
            return $value !== null;
        });
    }

    /**
     * Merge multiple menu configurations
     */
    public function mergeMenus(array ...$menus): array
    {
        $merged = [];

        foreach ($menus as $menu) {
            foreach ($menu as $item) {
                // Check for duplicates by slug or name
                $exists = false;
                foreach ($merged as $existingItem) {
                    if (
                        (isset($item['slug'], $existingItem['slug']) && $item['slug'] === $existingItem['slug']) ||
                        (isset($item['name'], $existingItem['name']) && $item['name'] === $existingItem['name'])
                    ) {
                        $exists = true;
                        break;
                    }
                }

                if (! $exists) {
                    $merged[] = $item;
                }
            }
        }

        return $merged;
    }
}
