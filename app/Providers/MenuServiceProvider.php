<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Services\Menu\MenuAggregator;
use App\Services\Menu\MenuRegistry;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register Menu Services as singletons
        $this->app->singleton(MenuRegistry::class);
        $this->app->singleton(MenuAggregator::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Use view composer to set menu data based on user role
        view()->composer('*', function ($view) {
            $menuAggregator = app(MenuAggregator::class);
            
            // Get vertical menu from aggregator
            $verticalMenuData = (object) $menuAggregator->getMenu('vertical');
            
            // Filter menu items based on permissions and roles
            $this->filterMenuByPermissions($verticalMenuData);

            // Load horizontal menu based on user role
            if (Auth::check()) {
                $horizontalMenuData = $this->loadHorizontalMenu();
                $this->filterMenuByPermissions($horizontalMenuData);
            } else {
                $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
                $horizontalMenuData = json_decode($horizontalMenuJson);
            }

            // Load quick create menu
            $quickCreateMenuJson = file_get_contents(base_path('resources/menu/quickCreateMenu.json'));
            $quickCreateMenuData = json_decode($quickCreateMenuJson);

            // Share all menuData to all the views
            $view->with('menuData', [$verticalMenuData, $horizontalMenuData, $quickCreateMenuData]);
            
            // Share menu aggregator for advanced features
            $view->with('menuAggregator', $menuAggregator);
        });
    }
    
    /**
     * Load appropriate horizontal menu based on user role
     */
    protected function loadHorizontalMenu()
    {
        $user = Auth::user();
        
        if ($user->hasRole('tenant')) {
            // Load tenant horizontal menu for tenant users
            $tenantMenuPath = base_path('Modules/MultiTenancyCore/resources/menu/tenantHorizontalMenu.json');
            if (file_exists($tenantMenuPath)) {
                $horizontalMenuJson = file_get_contents($tenantMenuPath);
            } else {
                // Fallback to default if tenant menu doesn't exist
                $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
            }
        } elseif ($user->hasRole(['employee', 'field_employee', 'hr_manager', 'hr_executive', 'accounting_manager', 'accounting_executive', 'crm_manager', 'project_manager', 'sales_manager', 'sales_executive', 'inventory_manager', 'team_leader'])) {
            // Load unified employee menu for all employee types
            $employeeMenuPath = base_path('resources/menu/employeeUnifiedMenu.json');
            if (file_exists($employeeMenuPath)) {
                $horizontalMenuJson = file_get_contents($employeeMenuPath);
            } else {
                // Fallback to old employee menu if unified doesn't exist
                $horizontalMenuJson = file_get_contents(base_path('resources/menu/employeeHorizontalMenu.json'));
            }
        } else {
            // Default horizontal menu for admin/super_admin roles
            $horizontalMenuJson = file_get_contents(base_path('resources/menu/horizontalMenu.json'));
        }
        
        return json_decode($horizontalMenuJson);
    }
    
    /**
     * Filter menu items based on user permissions, roles, and addon availability
     */
    protected function filterMenuByPermissions(&$menuData)
    {
        if (!Auth::check()) {
            return;
        }
        
        $user = Auth::user();
        
        // Load module-specific menu permissions
        $modulePermissions = $this->loadModulePermissions();
        
        // Handle both array and object menu structures
        $menuItems = is_array($menuData) ? $menuData : ($menuData->menu ?? []);
        
        foreach ($menuItems as $key => &$menuItem) {
            // Convert to object if it's an array
            if (is_array($menuItem)) {
                $menuItem = (object) $menuItem;
            }
            
            // Skip headers
            if (isset($menuItem->menuHeader)) {
                continue;
            }
            
            // Check role restrictions
            if (isset($menuItem->role)) {
                if (!$user->hasRole($menuItem->role)) {
                    unset($menuItems[$key]);
                    continue;
                }
            }
            
            // Check addon availability
            if (isset($menuItem->addon)) {
                if (!$this->isAddonEnabled($menuItem->addon)) {
                    unset($menuItems[$key]);
                    continue;
                }
            }
            
            // Check menu item permissions
            if (isset($menuItem->permission)) {
                if (!$user->can($menuItem->permission)) {
                    unset($menuItems[$key]);
                    continue;
                }
            }
            
            // Check slug-based permissions from modules
            if (isset($menuItem->slug)) {
                $slug = is_array($menuItem->slug) ? $menuItem->slug[0] : $menuItem->slug;
                
                if (isset($modulePermissions[$slug])) {
                    if (!$user->can($modulePermissions[$slug])) {
                        unset($menuItems[$key]);
                        continue;
                    }
                }
            }
            
            // Filter submenu items recursively
            if (isset($menuItem->submenu)) {
                $this->filterSubmenuItems($menuItem, $user, $modulePermissions);
                
                // If all submenu items are removed, remove the parent menu
                if (empty($menuItem->submenu)) {
                    unset($menuItems[$key]);
                }
            }
        }
        
        // Re-index array
        $menuItems = array_values($menuItems);
        
        // Update the original data
        if (is_array($menuData)) {
            $menuData = $menuItems;
        } else {
            $menuData->menu = $menuItems;
        }
    }
    
    /**
     * Filter submenu items recursively
     */
    protected function filterSubmenuItems(&$parentMenuItem, $user, $modulePermissions)
    {
        // Handle both array and object submenu structures
        $submenuItems = is_array($parentMenuItem->submenu) ? $parentMenuItem->submenu : [];
        
        foreach ($submenuItems as $subKey => &$subMenuItem) {
            // Convert to object if it's an array
            if (is_array($subMenuItem)) {
                $subMenuItem = (object) $subMenuItem;
            }
            
            // Check role restrictions
            if (isset($subMenuItem->role)) {
                if (!$user->hasRole($subMenuItem->role)) {
                    unset($submenuItems[$subKey]);
                    continue;
                }
            }
            
            // Check addon availability
            if (isset($subMenuItem->addon)) {
                if (!$this->isAddonEnabled($subMenuItem->addon)) {
                    unset($submenuItems[$subKey]);
                    continue;
                }
            }
            
            // Check submenu item permissions
            if (isset($subMenuItem->permission)) {
                if (!$user->can($subMenuItem->permission)) {
                    unset($submenuItems[$subKey]);
                    continue;
                }
            }
            
            // Check slug-based permissions
            if (isset($subMenuItem->slug)) {
                $subSlug = is_array($subMenuItem->slug) ? $subMenuItem->slug[0] : $subMenuItem->slug;
                
                if (isset($modulePermissions[$subSlug])) {
                    if (!$user->can($modulePermissions[$subSlug])) {
                        unset($submenuItems[$subKey]);
                        continue;
                    }
                }
            }
            
            // Handle nested submenus (3rd level)
            if (isset($subMenuItem->submenu)) {
                $this->filterSubmenuItems($subMenuItem, $user, $modulePermissions);
                
                // If all nested submenu items are removed, remove the parent submenu
                if (empty($subMenuItem->submenu)) {
                    unset($submenuItems[$subKey]);
                }
            }
        }
        
        // Re-index submenu array
        $parentMenuItem->submenu = array_values($submenuItems);
    }
    
    /**
     * Load module-specific menu permissions
     */
    protected function loadModulePermissions()
    {
        $permissions = [];
        
        // Use MenuRegistry to get permissions if available
        if (app()->bound(MenuRegistry::class)) {
            $menuRegistry = app(MenuRegistry::class);
            $permissions = $menuRegistry->getAllPermissions();
        }
        
        return $permissions;
    }
    
    /**
     * Check if an addon is enabled
     */
    protected function isAddonEnabled($addonName)
    {
        try {
            $addonService = app(\App\Services\AddonService\IAddonService::class);
            return $addonService->isAddonEnabled($addonName);
        } catch (\Exception $e) {
            // If AddonService is not available, assume addon is enabled
            return true;
        }
    }
}