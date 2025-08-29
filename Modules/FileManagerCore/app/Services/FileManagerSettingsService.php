<?php

namespace Modules\FileManagerCore\Services;

use App\Services\Settings\ModuleSettingsService;

class FileManagerSettingsService
{
    protected ModuleSettingsService $settingsService;

    protected string $module = 'FileManagerCore';

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get a setting value with fallback to default
     */
    public function get(string $key, $default = null)
    {
        return $this->settingsService->get($this->module, $key, $default);
    }

    /**
     * Set a setting value
     */
    public function set(string $key, $value): bool
    {
        return $this->settingsService->set($this->module, $key, $value);
    }

    /**
     * Get default storage disk
     */
    public function getDefaultDisk(): string
    {
        // First check module settings, then fall back to config
        $settingValue = $this->get('filemanager_default_disk', null);
        if ($settingValue) {
            return $settingValue;
        }

        // Fall back to config file setting
        return config('filemanagercore.default_disk', 'public');
    }

    /**
     * Get maximum file size in bytes
     */
    public function getMaxFileSize(): int
    {
        $sizeInKb = (int) $this->get('filemanager_max_file_size', 10240);

        return $sizeInKb * 1024; // Convert to bytes
    }

    /**
     * Get user storage quota in bytes
     */
    public function getUserQuota(): int
    {
        $quotaInMb = (int) $this->get('filemanager_user_quota', 1024);

        return $quotaInMb * 1024 * 1024; // Convert to bytes
    }

    /**
     * Get department storage quota in bytes
     */
    public function getDepartmentQuota(): int
    {
        $quotaInGb = (int) $this->get('filemanager_dept_quota', 10);

        return $quotaInGb * 1024 * 1024 * 1024; // Convert to bytes
    }

    /**
     * Get allowed image MIME types
     */
    public function getAllowedImageTypes(): array
    {
        $types = $this->get('filemanager_allowed_image_types', [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        ]);

        return is_array($types) ? $types : json_decode($types, true) ?? [];
    }

    /**
     * Get allowed document MIME types
     */
    public function getAllowedDocumentTypes(): array
    {
        $types = $this->get('filemanager_allowed_document_types', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ]);

        return is_array($types) ? $types : json_decode($types, true) ?? [];
    }

    /**
     * Get all allowed MIME types
     */
    public function getAllowedMimeTypes(): array
    {
        return array_merge(
            $this->getAllowedImageTypes(),
            $this->getAllowedDocumentTypes()
        );
    }

    /**
     * Check if thumbnail generation is enabled
     */
    public function isThumbnailEnabled(): bool
    {
        return (bool) $this->get('filemanager_thumbnail_enabled', true);
    }

    /**
     * Get thumbnail configuration
     */
    public function getThumbnailConfig(): array
    {
        return [
            'enabled' => $this->isThumbnailEnabled(),
            'max_width' => (int) $this->get('filemanager_thumbnail_max_width', 300),
            'max_height' => (int) $this->get('filemanager_thumbnail_max_height', 300),
            'quality' => (int) $this->get('filemanager_thumbnail_quality', 80),
            'disk' => $this->get('filemanager_thumbnail_disk', 'public'),
        ];
    }

    /**
     * Check if a MIME type is allowed
     */
    public function isMimeTypeAllowed(string $mimeType): bool
    {
        return in_array($mimeType, $this->getAllowedMimeTypes());
    }
}
