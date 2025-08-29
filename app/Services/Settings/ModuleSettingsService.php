<?php

namespace App\Services\Settings;

use App\Contracts\Settings\ModuleSettingsInterface;
use App\Models\ModuleSetting;
use App\Models\SettingHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ModuleSettingsService implements ModuleSettingsInterface
{
    /**
     * Get a module setting value
     */
    public function get(string $module, string $key, $default = null)
    {
        $settings = $this->getModuleSettingsCached($module);
        
        return data_get($settings, $key, $default);
    }

    /**
     * Set a module setting value
     */
    public function set(string $module, string $key, $value): bool
    {
        $setting = ModuleSetting::where('module', $module)
            ->where('key', $key)
            ->first();
            
        $oldValue = $setting?->value;
        
        if (!$setting) {
            // If setting doesn't exist, create it
            $setting = ModuleSetting::create([
                'module' => $module,
                'key' => $key,
                'value' => $value,
                'type' => $this->detectType($value),
            ]);
        } else {
            // Update the type in case it changed
            $setting->type = $this->detectType($value);
            $setting->value = $value;
            $setting->save();
        }
        
        // Log the change
        if ($oldValue !== $value) {
            SettingHistory::logChange('module', $key, $oldValue, $value, $module);
        }
        
        // Clear cache
        $this->clearCache($module);
        
        return true;
    }

    /**
     * Get all settings for a module
     */
    public function getModuleSettings(string $module): Collection
    {
        return collect($this->getModuleSettingsCached($module));
    }

    /**
     * Delete all settings for a module
     */
    public function deleteModuleSettings(string $module): bool
    {
        $settings = ModuleSetting::where('module', $module)->get();
        
        foreach ($settings as $setting) {
            SettingHistory::logChange('module', $setting->key, $setting->value, null, $module);
        }
        
        ModuleSetting::where('module', $module)->delete();
        
        $this->clearCache($module);
        
        return true;
    }

    /**
     * Get all module settings grouped
     */
    public function getAllGrouped(): Collection
    {
        return Cache::remember('module_settings_all', 3600, function () {
            return ModuleSetting::getAllGrouped();
        });
    }

    /**
     * Set multiple settings for a module
     */
    public function setMultiple(string $module, array $settings): bool
    {
        foreach ($settings as $key => $value) {
            $this->set($module, $key, $value);
        }
        
        return true;
    }

    /**
     * Check if module has any settings
     */
    public function hasSettings(string $module): bool
    {
        return ModuleSetting::where('module', $module)->exists();
    }

    /**
     * Get module settings from cache
     */
    private function getModuleSettingsCached(string $module): array
    {
        return Cache::remember("module_settings_{$module}", 3600, function () use ($module) {
            return ModuleSetting::module($module)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->value];
                })
                ->toArray();
        });
    }

    /**
     * Clear module cache
     */
    private function clearCache(string $module): void
    {
        Cache::forget("module_settings_{$module}");
        Cache::forget('module_settings_all');
        Cache::forget('global_settings');
    }

    /**
     * Detect the type of a value
     */
    private function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        
        if (is_int($value)) {
            return 'integer';
        }
        
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        
        return 'string';
    }
}