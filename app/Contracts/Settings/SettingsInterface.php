<?php

namespace App\Contracts\Settings;

use Illuminate\Support\Collection;

interface SettingsInterface
{
    /**
     * Get a setting value
     *
     * @param  mixed  $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set a setting value
     *
     * @param  mixed  $value
     */
    public function set(string $key, $value): bool;

    /**
     * Get settings by category
     */
    public function getByCategory(string $category): Collection;

    /**
     * Get multiple settings
     */
    public function getMultiple(array $keys): array;

    /**
     * Set multiple settings
     */
    public function setMultiple(array $settings): bool;

    /**
     * Delete a setting
     */
    public function delete(string $key): bool;

    /**
     * Get all settings
     */
    public function all(): Collection;

    /**
     * Refresh cache
     */
    public function refresh(): void;
}
