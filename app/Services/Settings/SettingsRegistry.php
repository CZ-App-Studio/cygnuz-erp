<?php

namespace App\Services\Settings;

use Illuminate\Support\Collection;

class SettingsRegistry
{
    /**
     * Registered modules and their settings
     */
    protected array $modules = [];

    /**
     * Registered settings metadata
     */
    protected array $metadata = [];

    /**
     * Register a module with its settings
     */
    public function registerModule(string $module, array $config): void
    {
        $this->modules[strtolower($module)] = $config;
    }

    /**
     * Register setting metadata
     */
    public function registerSetting(string $key, array $metadata): void
    {
        $this->metadata[$key] = $metadata;
    }

    /**
     * Get all registered modules
     */
    public function getRegisteredModules(): array
    {
        return $this->modules;
    }

    /**
     * Get module configuration
     */
    public function getModuleConfig(string $module): ?array
    {
        return $this->modules[strtolower($module)] ?? null;
    }

    /**
     * Check if module is registered
     */
    public function hasModule(string $module): bool
    {
        return isset($this->modules[strtolower($module)]);
    }

    /**
     * Get setting metadata
     */
    public function getSettingMetadata(string $key): ?array
    {
        return $this->metadata[$key] ?? null;
    }

    /**
     * Get all metadata for a category
     */
    public function getCategoryMetadata(string $category): Collection
    {
        return collect($this->metadata)->filter(function ($meta, $key) use ($category) {
            return ($meta['category'] ?? '') === $category;
        });
    }

    /**
     * Register multiple settings at once
     */
    public function registerMultiple(array $settings): void
    {
        foreach ($settings as $key => $metadata) {
            $this->registerSetting($key, $metadata);
        }
    }

    /**
     * Get module handler class
     */
    public function getModuleHandler(string $module): ?string
    {
        $config = $this->getModuleConfig($module);
        return $config['handler'] ?? null;
    }

    /**
     * Get module permissions
     */
    public function getModulePermissions(string $module): array
    {
        $config = $this->getModuleConfig($module);
        return $config['permissions'] ?? [];
    }

    /**
     * Clear all registrations
     */
    public function clear(): void
    {
        $this->modules = [];
        $this->metadata = [];
    }
}