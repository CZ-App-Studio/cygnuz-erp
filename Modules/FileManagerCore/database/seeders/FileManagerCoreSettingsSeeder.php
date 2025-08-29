<?php

namespace Modules\FileManagerCore\database\seeders;

use App\Models\ModuleSetting;
use Illuminate\Database\Seeder;

class FileManagerCoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Storage Configuration - All 4 settings kept (working)
            'filemanager_default_disk' => 'local',
            'filemanager_max_file_size' => '10240', // 10MB in KB
            'filemanager_user_quota' => '1024', // 1GB in MB
            'filemanager_dept_quota' => '10', // 10GB

            // File Types - 2 settings kept (working)
            'filemanager_allowed_image_types' => [
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'
            ],
            'filemanager_allowed_document_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'text/csv'
            ],

            // Thumbnails & Processing - All 5 settings kept (working)
            'filemanager_thumbnail_enabled' => true,
            'filemanager_thumbnail_max_width' => '300',
            'filemanager_thumbnail_max_height' => '300',
            'filemanager_thumbnail_quality' => '80',
            'filemanager_thumbnail_disk' => 'public',
        ];

        foreach ($settings as $key => $value) {
            ModuleSetting::firstOrCreate(
                [
                    'module' => 'FileManagerCore',
                    'key' => $key,
                ],
                [
                    'value' => is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value),
                    'type' => $this->getSettingType($value),
                    'description' => $this->getSettingDescription($key),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('✅ FileManagerCore settings seeded successfully');
        $this->command->info('Total settings seeded: ' . count($settings));
    }

    /**
     * Get the appropriate type for a setting value
     */
    private function getSettingType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        } elseif (is_array($value)) {
            return 'json';
        } elseif (is_numeric($value) && !str_contains($value, '.')) {
            return 'integer';
        }
        
        return 'string';
    }

    /**
     * Get description for a setting key
     */
    private function getSettingDescription(string $key): string
    {
        $descriptions = [
            // Storage Configuration - All 4 settings kept (working)
            'filemanager_default_disk' => 'Default storage disk for file uploads',
            'filemanager_max_file_size' => 'Maximum file size allowed for uploads in kilobytes',
            'filemanager_user_quota' => 'Storage quota per user in megabytes (0 for unlimited)',
            'filemanager_dept_quota' => 'Storage quota per department in gigabytes (0 for unlimited)',

            // File Types - 2 settings kept (working)
            'filemanager_allowed_image_types' => 'MIME types allowed for image uploads',
            'filemanager_allowed_document_types' => 'MIME types allowed for document uploads',

            // Thumbnails & Processing - All 5 settings kept (working)
            'filemanager_thumbnail_enabled' => 'Automatically generate thumbnails for images',
            'filemanager_thumbnail_max_width' => 'Maximum width for generated thumbnails',
            'filemanager_thumbnail_max_height' => 'Maximum height for generated thumbnails',
            'filemanager_thumbnail_quality' => 'JPEG quality for thumbnails (1-100)',
            'filemanager_thumbnail_disk' => 'Storage disk for thumbnail files',
        ];

        return $descriptions[$key] ?? 'Setting for ' . str_replace(['filemanager_', '_'], ['', ' '], $key);
    }
}