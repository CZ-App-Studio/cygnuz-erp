<?php

namespace Modules\FileManagerCore\Enums;

enum FileVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case INTERNAL = 'internal';

    /**
     * Get human-readable label for the visibility level
     */
    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
            self::INTERNAL => 'Internal',
        };
    }

    /**
     * Get description for the visibility level
     */
    public function description(): string
    {
        return match ($this) {
            self::PUBLIC => 'Accessible to anyone with the link',
            self::PRIVATE => 'Only accessible to specific users',
            self::INTERNAL => 'Accessible to all company members',
        };
    }

    /**
     * Check if this visibility allows external access
     */
    public function allowsExternalAccess(): bool
    {
        return $this === self::PUBLIC;
    }
}
