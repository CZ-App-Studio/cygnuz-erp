<?php

namespace App\Contracts\Settings;

use Illuminate\Support\Collection;

interface SettingsInterface
{
    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function set(string $key, $value): bool;

    /**
     * Get settings by category
     *
     * @param string $category
     * @return Collection
     */
    public function getByCategory(string $category): Collection;

    /**
     * Get multiple settings
     *
     * @param array $keys
     * @return array
     */
    public function getMultiple(array $keys): array;

    /**
     * Set multiple settings
     *
     * @param array $settings
     * @return bool
     */
    public function setMultiple(array $settings): bool;

    /**
     * Delete a setting
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * Get all settings
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Refresh cache
     *
     * @return void
     */
    public function refresh(): void;
}