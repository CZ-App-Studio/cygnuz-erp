<?php

return [
    'name' => 'FileManagerCore',

    // Default storage disk
    'default_disk' => env('FILEMANAGER_DEFAULT_DISK', 'public'),

    // Maximum file size in KB
    'max_file_size' => env('FILEMANAGER_MAX_FILE_SIZE', 10240), // 10MB

    // Allowed MIME types
    'allowed_mime_types' => [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf', 'application/msword', 'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain', 'text/csv',
        'video/mp4', 'video/mpeg', 'video/quicktime',
        'audio/mpeg', 'audio/wav', 'audio/mp3',
        'application/zip', 'application/x-rar-compressed',
    ],

    // Thumbnail configuration
    'thumbnail' => [
        'enabled' => true,
        'max_width' => 300,
        'max_height' => 300,
        'quality' => 80,
        'disk' => env('FILEMANAGER_THUMBNAIL_DISK', 'public'),
    ],

    // Storage providers configuration
    'providers' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],
    ],

    // Storage quotas in bytes
    'quotas' => [
        'per_user' => env('FILEMANAGER_USER_QUOTA', 1073741824), // 1GB
        'per_department' => env('FILEMANAGER_DEPT_QUOTA', 10737418240), // 10GB
    ],

    // File versioning
    'versioning' => [
        'enabled' => env('FILEMANAGER_VERSIONING', true),
        'max_versions' => env('FILEMANAGER_MAX_VERSIONS', 5),
    ],

    // Security settings
    'security' => [
        'virus_scanning' => env('FILEMANAGER_VIRUS_SCAN', false),
        'checksum_verification' => true,
        'encrypt_files' => env('FILEMANAGER_ENCRYPT_FILES', false),
    ],

    // Cleanup settings
    'cleanup' => [
        'orphaned_files_after_days' => 30,
        'deleted_files_after_days' => 90,
        'temp_files_after_hours' => 24,
    ],
];
