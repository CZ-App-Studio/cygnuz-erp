<?php

namespace App\Http\Controllers;

use App\Http\Responses\Success;
use App\Http\Responses\Error;
use App\Services\Menu\MenuAggregator;
use App\Services\Menu\MenuRegistry;
use App\Models\UserMenuPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MenuController extends Controller
{
    protected MenuAggregator $menuAggregator;
    protected MenuRegistry $menuRegistry;

    public function __construct(MenuAggregator $menuAggregator, MenuRegistry $menuRegistry)
    {
        $this->menuAggregator = $menuAggregator;
        $this->menuRegistry = $menuRegistry;
    }

    /**
     * Search menu items
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'menu_type' => 'nullable|in:vertical,horizontal',
        ]);

        $query = $request->input('query');
        $menuType = $request->input('menu_type', 'vertical');

        $results = $this->menuAggregator->searchMenu($query, $menuType);

        // Track search for analytics
        $this->trackMenuSearch($query);

        return Success::response([
            'results' => $results->take(20), // Limit results
            'count' => $results->count(),
        ]);
    }

    /**
     * Get user's favorite menu items
     */
    public function getFavorites(): JsonResponse
    {
        $userId = Auth::id();
        
        // This would fetch from the user_menu_favorites table once it's implemented
        $favorites = Cache::remember("user.{$userId}.menu.favorites", 3600, function () use ($userId) {
            // Placeholder - would fetch from database
            return [];
        });

        return Success::response([
            'favorites' => $favorites,
        ]);
    }

    /**
     * Add menu item to favorites
     */
    public function addFavorite(Request $request): JsonResponse
    {
        $request->validate([
            'menu_slug' => 'required|string',
            'menu_name' => 'required|string',
            'menu_url' => 'nullable|string',
        ]);

        // This would save to user_menu_favorites table
        // For now, using the existing preferences table
        $userId = Auth::id();
        
        // Clear cache
        Cache::forget("user.{$userId}.menu.favorites");

        return Success::response([
            'message' => 'Menu item added to favorites',
        ]);
    }

    /**
     * Get recently accessed menu items
     */
    public function getRecent(): JsonResponse
    {
        $userId = Auth::id();
        
        $recent = Cache::remember("user.{$userId}.menu.recent", 3600, function () use ($userId) {
            // Placeholder - would fetch from user_recent_menus table
            return [];
        });

        return Success::response([
            'recent' => $recent,
        ]);
    }

    /**
     * Track menu item access
     */
    public function trackAccess(Request $request): JsonResponse
    {
        $request->validate([
            'menu_slug' => 'required|string',
        ]);

        $userId = Auth::id();
        $menuSlug = $request->input('menu_slug');

        // This would update user_recent_menus table
        // For now, just clear the cache
        Cache::forget("user.{$userId}.menu.recent");

        return Success::response([
            'message' => 'Menu access tracked',
        ]);
    }

    /**
     * Get menu profiles for user
     */
    public function getProfiles(): JsonResponse
    {
        $userId = Auth::id();
        
        // Placeholder - would fetch from menu_profiles table
        $profiles = [
            [
                'id' => 1,
                'name' => 'Default',
                'slug' => 'default',
                'is_active' => true,
            ],
        ];

        return Success::response([
            'profiles' => $profiles,
        ]);
    }

    /**
     * Switch menu profile
     */
    public function switchProfile(Request $request): JsonResponse
    {
        $request->validate([
            'profile_id' => 'required|integer',
        ]);

        // This would update the active profile in database
        
        return Success::response([
            'message' => 'Profile switched successfully',
        ]);
    }

    /**
     * Refresh menu cache
     */
    public function refreshCache(): JsonResponse
    {
        if (!Auth::user()->can('manage-system')) {
            return Error::response('Unauthorized to refresh menu cache');
        }

        $this->menuAggregator->clearCache();
        
        // Pre-warm cache
        $this->menuAggregator->getMenu('vertical', true);
        $this->menuAggregator->getMenu('horizontal', true);

        return Success::response([
            'message' => 'Menu cache refreshed successfully',
        ]);
    }

    /**
     * Get menu statistics
     */
    public function getStatistics(): JsonResponse
    {
        if (!Auth::user()->can('manage-system')) {
            return Error::response('Unauthorized to view menu statistics');
        }

        $verticalMenu = $this->menuAggregator->getMenu('vertical');
        $horizontalMenu = $this->menuAggregator->getMenu('horizontal');

        $stats = [
            'vertical_items' => count($verticalMenu['menu'] ?? []),
            'horizontal_items' => count($horizontalMenu['menu'] ?? []),
            'cache_status' => [
                'vertical' => Cache::has('menu.aggregated.vertical'),
                'horizontal' => Cache::has('menu.aggregated.horizontal'),
            ],
            'modules_with_menus' => $this->getModulesWithMenus(),
        ];

        return Success::response($stats);
    }

    /**
     * Track menu search for analytics
     */
    protected function trackMenuSearch(string $query): void
    {
        // Could be used for analytics or improving search
        Cache::increment('menu.search.count');
        
        // Track popular searches
        $popularSearches = Cache::get('menu.search.popular', []);
        $popularSearches[$query] = ($popularSearches[$query] ?? 0) + 1;
        Cache::put('menu.search.popular', $popularSearches, 86400); // 24 hours
    }

    /**
     * Get modules that have menu items
     */
    protected function getModulesWithMenus(): array
    {
        $modules = [];
        $modulesPath = base_path('Modules');

        if (is_dir($modulesPath)) {
            foreach (scandir($modulesPath) as $module) {
                if ($module === '.' || $module === '..') {
                    continue;
                }
                
                $menuPath = "{$modulesPath}/{$module}/resources/menu/verticalMenu.json";
                if (file_exists($menuPath)) {
                    $modules[] = $module;
                }
            }
        }

        return $modules;
    }
}