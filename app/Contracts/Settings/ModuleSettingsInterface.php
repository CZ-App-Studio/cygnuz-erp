<?php

namespace App\Contracts\Settings;

use Illuminate\Support\Collection;

interface ModuleSettingsInterface
{
    /**
     * Get a module setting value
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $module, string $key, $default = null);

    /**
     * Set a module setting value
     *
     * @param  mixed  $value
     */
    public function set(string $module, string $key, $value): bool;

    /**
     * Get all settings for a module
     */
    public function getModuleSettings(string $module): Collection;

    /**
     * Delete all settings for a module
     */
    public function deleteModuleSettings(string $module): bool;

    /**
     * Get all module settings grouped
     */
    public function getAllGrouped(): Collection;

    /**
     * Set multiple settings for a module
     */
    public function setMultiple(string $module, array $settings): bool;

    /**
     * Check if module has any settings
     */
    public function hasSettings(string $module): bool;
}
