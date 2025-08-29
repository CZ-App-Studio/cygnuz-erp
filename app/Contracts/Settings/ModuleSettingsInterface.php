<?php

namespace App\Contracts\Settings;

use Illuminate\Support\Collection;

interface ModuleSettingsInterface
{
    /**
     * Get a module setting value
     *
     * @param string $module
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $module, string $key, $default = null);

    /**
     * Set a module setting value
     *
     * @param string $module
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set(string $module, string $key, $value): bool;

    /**
     * Get all settings for a module
     *
     * @param string $module
     * @return Collection
     */
    public function getModuleSettings(string $module): Collection;

    /**
     * Delete all settings for a module
     *
     * @param string $module
     * @return bool
     */
    public function deleteModuleSettings(string $module): bool;

    /**
     * Get all module settings grouped
     *
     * @return Collection
     */
    public function getAllGrouped(): Collection;

    /**
     * Set multiple settings for a module
     *
     * @param string $module
     * @param array $settings
     * @return bool
     */
    public function setMultiple(string $module, array $settings): bool;

    /**
     * Check if module has any settings
     *
     * @param string $module
     * @return bool
     */
    public function hasSettings(string $module): bool;
}