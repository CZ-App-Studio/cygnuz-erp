<?php

namespace App\Enums;

enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';

    /**
     * Get the label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => __('Active'),
            self::INACTIVE => __('Inactive'),
            self::DELETED => __('Deleted'),
        };
    }

    /**
     * Get the Bootstrap color class for the status
     */
    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'warning',
            self::DELETED => 'danger',
        };
    }

    /**
     * Get the badge HTML for the status
     */
    public function badge(): string
    {
        return '<span class="badge bg-label-'.$this->color().'">'.$this->label().'</span>';
    }
}
