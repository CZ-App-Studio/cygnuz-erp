<?php

namespace App\Services\Settings;

use App\Contracts\Settings\SettingsInterface;
use App\Models\SettingHistory;
use App\Models\SystemSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService implements SettingsInterface
{
    /**
     * Get a setting value
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->getAllCached();

        return data_get($settings, $key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value): bool
    {
        $setting = SystemSetting::where('key', $key)->first();
        $oldValue = $setting?->value;

        if (! $setting) {
            // If setting doesn't exist, create it
            $setting = SystemSetting::create([
                'key' => $key,
                'value' => $value,
                'type' => $this->detectType($value),
                'category' => $this->detectCategory($key),
            ]);
        } else {
            $setting->value = $value;
            $setting->save();
        }

        // Log the change
        if ($oldValue !== $value) {
            SettingHistory::logChange('system', $key, $oldValue, $value);
        }

        // Clear cache
        $this->clearCache();

        return true;
    }

    /**
     * Get settings by category
     */
    public function getByCategory(string $category): Collection
    {
        return SystemSetting::category($category)->get()->mapWithKeys(function ($setting) {
            return [$setting->key => $setting->value];
        });
    }

    /**
     * Get multiple settings
     */
    public function getMultiple(array $keys): array
    {
        $settings = $this->getAllCached();
        $result = [];

        foreach ($keys as $key => $default) {
            if (is_numeric($key)) {
                $result[$default] = data_get($settings, $default);
            } else {
                $result[$key] = data_get($settings, $key, $default);
            }
        }

        return $result;
    }

    /**
     * Set multiple settings
     */
    public function setMultiple(array $settings): bool
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value);
        }

        return true;
    }

    /**
     * Delete a setting
     */
    public function delete(string $key): bool
    {
        $setting = SystemSetting::where('key', $key)->first();

        if ($setting) {
            $oldValue = $setting->value;
            $setting->delete();

            // Log the deletion
            SettingHistory::logChange('system', $key, $oldValue, null);

            $this->clearCache();

            return true;
        }

        return false;
    }

    /**
     * Get all settings (cached)
     */
    public function all(): Collection
    {
        return collect($this->getAllCached());
    }

    /**
     * Refresh cache
     */
    public function refresh(): void
    {
        $this->clearCache();
        $this->getAllCached();
    }

    /**
     * Get all settings from cache or database
     */
    private function getAllCached(): array
    {
        return Cache::remember('system_settings', 3600, function () {
            return SystemSetting::all()->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->value];
            })->toArray();
        });
    }

    /**
     * Clear settings cache
     */
    private function clearCache(): void
    {
        Cache::forget('system_settings');
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

    /**
     * Detect category from key
     */
    private function detectCategory(string $key): string
    {
        $parts = explode('_', $key);

        // Map common prefixes to categories
        $categoryMap = [
            'app' => 'general',
            'company' => 'company',
            'currency' => 'regional',
            'map' => 'maps',
            'ai' => 'ai',
            'payroll' => 'payroll',
            'm_' => 'mobile',
        ];

        foreach ($categoryMap as $prefix => $category) {
            if (str_starts_with($key, $prefix)) {
                return $category;
            }
        }

        return 'general';
    }
}
