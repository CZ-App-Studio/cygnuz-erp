<?php

namespace App\Enums;

enum LeaveRequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case CANCELLED_BY_ADMIN = 'cancelled_by_admin';

    /**
     * Get the label for the leave request status
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::APPROVED => __('Approved'),
            self::REJECTED => __('Rejected'),
            self::CANCELLED => __('Cancelled'),
            self::CANCELLED_BY_ADMIN => __('Cancelled by Admin'),
        };
    }

    /**
     * Get the Bootstrap color class for the leave request status
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::CANCELLED => 'secondary',
            self::CANCELLED_BY_ADMIN => 'danger',
        };
    }

    /**
     * Get the badge HTML for the leave request status
     */
    public function badge(): string
    {
        return '<span class="badge bg-label-'.$this->color().'">'.$this->label().'</span>';
    }
}
