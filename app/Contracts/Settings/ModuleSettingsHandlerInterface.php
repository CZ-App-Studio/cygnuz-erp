<?php

namespace App\Contracts\Settings;

interface ModuleSettingsHandlerInterface
{
    /**
     * Get the settings definition for the module
     */
    public function getSettingsDefinition(): array;

    /**
     * Get the view path for module settings
     */
    public function getSettingsView(): string;

    /**
     * Validate settings data
     */
    public function validateSettings(array $data): array;

    /**
     * Save settings data
     */
    public function saveSettings(array $data): bool;

    /**
     * Get required permissions
     */
    public function getSettingsPermissions(): array;
}
