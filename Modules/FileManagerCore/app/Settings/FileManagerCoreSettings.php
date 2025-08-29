<?php

namespace Modules\FileManagerCore\app\Settings;

use App\Services\Settings\BaseModuleSettings;

class FileManagerCoreSettings extends BaseModuleSettings
{
    protected string $module = 'FileManagerCore';

    /**
     * Get module display name
     */
    public function getModuleName(): string
    {
        return __('File Manager Core Settings');
    }

    /**
     * Get module description
     */
    public function getModuleDescription(): string
    {
        return __('Configure file upload limits, storage quotas, thumbnails, security, and cleanup settings');
    }

    /**
     * Get module icon
     */
    public function getModuleIcon(): string
    {
        return 'bx bx-file';
    }

    /**
     * Define module settings
     */
    protected function define(): array
    {
        return [
            'storage_configuration' => [
                'filemanager_default_disk' => [
                    'type' => 'select',
                    'label' => __('Default Storage Disk'),
                    'help' => __('Default storage disk for file uploads'),
                    'options' => [
                        'local' => __('Private Local Storage'),
                        'public' => __('Public Local Storage')
                    ],
                    'default' => 'local',
                    'validation' => 'required|in:local,public'
                ],
                'filemanager_max_file_size' => [
                    'type' => 'number',
                    'label' => __('Maximum File Size (KB)'),
                    'help' => __('Maximum file size allowed for uploads in kilobytes'),
                    'default' => '10240',
                    'validation' => 'required|numeric|min:100|max:102400'
                ],
                'filemanager_user_quota' => [
                    'type' => 'number',
                    'label' => __('User Storage Quota (MB)'),
                    'help' => __('Storage quota per user in megabytes (0 for unlimited)'),
                    'default' => '1024',
                    'validation' => 'required|numeric|min:0|max:10240'
                ],
                'filemanager_dept_quota' => [
                    'type' => 'number',
                    'label' => __('Department Storage Quota (GB)'),
                    'help' => __('Storage quota per department in gigabytes (0 for unlimited)'),
                    'default' => '10',
                    'validation' => 'required|numeric|min:0|max:100'
                ]
            ],

            'file_types_security' => [
                'filemanager_allowed_image_types' => [
                    'type' => 'multiselect',
                    'label' => __('Allowed Image Types'),
                    'help' => __('MIME types allowed for image uploads'),
                    'options' => [
                        'image/jpeg' => 'JPEG',
                        'image/jpg' => 'JPG',
                        'image/png' => 'PNG',
                        'image/gif' => 'GIF',
                        'image/webp' => 'WebP',
                        'image/svg+xml' => 'SVG'
                    ],
                    'default' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'],
                    'validation' => 'array'
                ],
                'filemanager_allowed_document_types' => [
                    'type' => 'multiselect',
                    'label' => __('Allowed Document Types'),
                    'help' => __('MIME types allowed for document uploads'),
                    'options' => [
                        'application/pdf' => 'PDF',
                        'application/msword' => 'DOC',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
                        'application/vnd.ms-excel' => 'XLS',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
                        'text/plain' => 'TXT',
                        'text/csv' => 'CSV'
                    ],
                    'default' => [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain',
                        'text/csv'
                    ],
                    'validation' => 'array'
                ]
            ],

            'thumbnails_processing' => [
                'filemanager_thumbnail_enabled' => [
                    'type' => 'toggle',
                    'label' => __('Enable Thumbnail Generation'),
                    'help' => __('Automatically generate thumbnails for images'),
                    'default' => true,
                    'validation' => 'boolean'
                ],
                'filemanager_thumbnail_max_width' => [
                    'type' => 'number',
                    'label' => __('Thumbnail Max Width (px)'),
                    'help' => __('Maximum width for generated thumbnails'),
                    'default' => '300',
                    'validation' => 'required|numeric|min:50|max:1000'
                ],
                'filemanager_thumbnail_max_height' => [
                    'type' => 'number',
                    'label' => __('Thumbnail Max Height (px)'),
                    'help' => __('Maximum height for generated thumbnails'),
                    'default' => '300',
                    'validation' => 'required|numeric|min:50|max:1000'
                ],
                'filemanager_thumbnail_quality' => [
                    'type' => 'number',
                    'label' => __('Thumbnail Quality (%)'),
                    'help' => __('JPEG quality for thumbnails (1-100)'),
                    'default' => '80',
                    'validation' => 'required|numeric|min:1|max:100'
                ],
                'filemanager_thumbnail_disk' => [
                    'type' => 'select',
                    'label' => __('Thumbnail Storage Disk'),
                    'help' => __('Storage disk for thumbnail files'),
                    'options' => [
                        'local' => __('Private Local Storage'),
                        'public' => __('Public Local Storage')
                    ],
                    'default' => 'public',
                    'validation' => 'required|in:local,public'
                ]
            ]
        ];
    }

    /**
     * Get settings permissions
     */
    public function getSettingsPermissions(): array
    {
        return ['manage-filemanagercore-settings'];
    }

    /**
     * Get the view path for module settings
     */
    public function getSettingsView(): string
    {
        return 'filemanagercore::settings.index';
    }
}