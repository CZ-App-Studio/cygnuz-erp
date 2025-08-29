<?php

namespace App\Contracts;

interface ModuleSettingsInterface
{
    /**
     * Get module identifier
     */
    public function getModuleKey(): string;

    /**
     * Get module display name
     */
    public function getModuleName(): string;

    /**
     * Get module description
     */
    public function getModuleDescription(): string;

    /**
     * Get module icon
     */
    public function getModuleIcon(): string;

    /**
     * Get settings definition
     */
    public function getSettingsDefinition(): array;

    /**
     * Get default values for settings
     */
    public function getDefaultValues(): array;

    /**
     * Get validation rules for settings
     */
    public function getValidationRules(): array;

    /**
     * Get setting value
     */
    public function get(string $key, $default = null);

    /**
     * Set setting value
     */
    public function set(string $key, $value): bool;

    /**
     * Get all settings
     */
    public function all(): array;

    /**
     * Reset all settings to defaults
     */
    public function reset(): bool;

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool;

    /**
     * Enable/disable module
     */
    public function setEnabled(bool $enabled): bool;
}