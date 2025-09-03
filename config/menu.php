<?php

return [
    /**
     * Enable the new menu aggregator system
     * Set to false to use the legacy JSON merging approach
     */
    'use_aggregator' => true,

    /**
     * Cache configuration for menu system
     */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // Cache time in seconds (1 hour)
        'tags' => ['menus'],
    ],

    /**
     * Menu header priorities
     * Lower numbers appear first
     */
    'header_priorities' => [
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
        'Artificial Intelligence' => 11,
    ],

    /**
     * Default menu item settings
     */
    'defaults' => [
        'icon' => 'menu-icon bx bx-circle',
        'priority' => 99,
        'menu_type' => 'vertical',
    ],

    /**
     * Core system menu items that should always be present
     * These won't be removed even if modules are disabled
     */
    'core_items' => [
        'dashboard',
        'users.index',
        'roles.index',
        'permissions.index',
        'settings.index',
    ],

    /**
     * Menu search configuration
     */
    'search' => [
        'enabled' => true,
        'min_characters' => 2,
        'fuzzy_match' => true,
    ],

    /**
     * User preferences
     */
    'user_preferences' => [
        'allow_pinning' => true,
        'allow_hiding' => true,
        'allow_reordering' => true,
        'max_pinned_items' => 10,
        'max_recent_items' => 5,
        'max_favorite_items' => 15,
    ],

    /**
     * Module menu registration
     * Modules can register their menus programmatically
     */
    'auto_discovery' => [
        'enabled' => true,
        'paths' => [
            'Modules/*/resources/menu/verticalMenu.json',
            'Modules/*/resources/menu/horizontalMenu.json',
        ],
    ],

    /**
     * Menu profiles
     * Predefined menu configurations for different roles
     */
    'profiles' => [
        'admin' => [
            'name' => 'Administrator',
            'description' => 'Full system access with all modules',
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Management focused menu items',
        ],
        'employee' => [
            'name' => 'Employee',
            'description' => 'Essential employee functions',
        ],
    ],

    /**
     * Database storage options
     */
    'database' => [
        'enabled' => false, // Set to true to use database storage
        'sync_on_boot' => true, // Sync JSON files to database on boot
        'priority' => 'database', // 'database' or 'files' - which takes precedence
    ],

    /**
     * Development mode settings
     */
    'dev_mode' => [
        'bypass_cache' => env('APP_DEBUG', false),
        'show_debug_info' => env('APP_DEBUG', false),
    ],
];
