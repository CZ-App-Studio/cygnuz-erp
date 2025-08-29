<?php

namespace Modules\FileManagerCore\Enums;

enum StorageProvider: string
{
    case LOCAL = 'local';
    case S3 = 's3';
    case GOOGLE_DRIVE = 'google_drive';
    case FTP = 'ftp';
    case SFTP = 'sftp';
    
    /**
     * Get human-readable label for the storage provider
     */
    public function label(): string
    {
        return match($this) {
            self::LOCAL => 'Local Storage',
            self::S3 => 'Amazon S3 / Compatible',
            self::GOOGLE_DRIVE => 'Google Drive',
            self::FTP => 'FTP Server',
            self::SFTP => 'SFTP Server',
        };
    }
    
    /**
     * Get the driver class for this provider
     */
    public function driverClass(): string
    {
        return match($this) {
            self::LOCAL => \Modules\FileManagerCore\Drivers\LocalStorageDriver::class,
            self::S3 => \Modules\FileManagerS3\Drivers\S3StorageDriver::class,
            self::GOOGLE_DRIVE => \Modules\FileManagerGoogleDrive\Drivers\GoogleDriveStorageDriver::class,
            self::FTP => \Modules\FileManagerFTP\Drivers\FTPStorageDriver::class,
            self::SFTP => \Modules\FileManagerFTP\Drivers\SFTPStorageDriver::class,
        };
    }
    
    /**
     * Check if this provider supports CDN
     */
    public function supportsCDN(): bool
    {
        return match($this) {
            self::S3 => true,
            default => false,
        };
    }
    
    /**
     * Check if this provider supports temporary URLs
     */
    public function supportsTemporaryUrls(): bool
    {
        return match($this) {
            self::S3, self::GOOGLE_DRIVE => true,
            default => false,
        };
    }
    
    /**
     * Check if this provider is always available (core system)
     */
    public function isCoreProvider(): bool
    {
        return $this === self::LOCAL;
    }
}