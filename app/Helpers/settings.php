<?php

use App\Services\Settings\ModuleSettingsService;
use App\Services\Settings\SettingsService;

if (!function_exists('setting')) {
    /**
     * Get or set a system setting
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function setting($key = null, $default = null)
    {
        $settings = app(SettingsService::class);
        
        if (is_null($key)) {
            return $settings;
        }
        
        if (is_array($key)) {
            return $settings->setMultiple($key);
        }
        
        return $settings->get($key, $default);
    }
}

if (!function_exists('module_setting')) {
    /**
     * Get or set a module setting
     *
     * @param string $module
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function module_setting(string $module, $key = null, $default = null)
    {
        $settings = app(ModuleSettingsService::class);
        
        if (is_null($key)) {
            return $settings->getModuleSettings($module);
        }
        
        if (is_array($key)) {
            return $settings->setMultiple($module, $key);
        }
        
        return $settings->get($module, $key, $default);
    }
}

if (!function_exists('settings_category')) {
    /**
     * Get all settings for a category
     *
     * @param string $category
     * @return \Illuminate\Support\Collection
     */
    function settings_category(string $category)
    {
        return app(SettingsService::class)->getByCategory($category);
    }
}

if (!function_exists('has_module_settings')) {
    /**
     * Check if a module has any settings
     *
     * @param string $module
     * @return bool
     */
    function has_module_settings(string $module): bool
    {
        return app(ModuleSettingsService::class)->hasSettings($module);
    }
}