<?php

namespace App\Contracts\Settings;

interface ModuleSettingsHandlerInterface
{
    /**
     * Get the settings definition for the module
     *
     * @return array
     */
    public function getSettingsDefinition(): array;

    /**
     * Get the view path for module settings
     *
     * @return string
     */
    public function getSettingsView(): string;

    /**
     * Validate settings data
     *
     * @param array $data
     * @return array
     */
    public function validateSettings(array $data): array;

    /**
     * Save settings data
     *
     * @param array $data
     * @return bool
     */
    public function saveSettings(array $data): bool;

    /**
     * Get required permissions
     *
     * @return array
     */
    public function getSettingsPermissions(): array;
}