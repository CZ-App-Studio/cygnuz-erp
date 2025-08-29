<?php

namespace Modules\FileManagerCore\Enums;

enum FileStatus: string
{
    case UPLOADING = 'uploading';
    case ACTIVE = 'active';
    case PROCESSING = 'processing';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';
    case FAILED = 'failed';
    
    /**
     * Get human-readable label for the file status
     */
    public function label(): string
    {
        return match($this) {
            self::UPLOADING => 'Uploading',
            self::ACTIVE => 'Active',
            self::PROCESSING => 'Processing',
            self::ARCHIVED => 'Archived',
            self::DELETED => 'Deleted',
            self::FAILED => 'Failed',
        };
    }
    
    /**
     * Get color code for status display
     */
    public function color(): string
    {
        return match($this) {
            self::UPLOADING => 'blue',
            self::ACTIVE => 'green',
            self::PROCESSING => 'yellow',
            self::ARCHIVED => 'gray',
            self::DELETED => 'red',
            self::FAILED => 'red',
        };
    }
    
    /**
     * Check if file is accessible in this status
     */
    public function isAccessible(): bool
    {
        return match($this) {
            self::ACTIVE, self::ARCHIVED => true,
            default => false,
        };
    }
    
    /**
     * Check if file can be modified in this status
     */
    public function isModifiable(): bool
    {
        return $this === self::ACTIVE;
    }
    
    /**
     * Get next possible statuses from current status
     */
    public function nextStatuses(): array
    {
        return match($this) {
            self::UPLOADING => [self::ACTIVE, self::FAILED],
            self::ACTIVE => [self::PROCESSING, self::ARCHIVED, self::DELETED],
            self::PROCESSING => [self::ACTIVE, self::FAILED],
            self::ARCHIVED => [self::ACTIVE, self::DELETED],
            self::DELETED => [self::ACTIVE],
            self::FAILED => [self::UPLOADING, self::DELETED],
        };
    }
}