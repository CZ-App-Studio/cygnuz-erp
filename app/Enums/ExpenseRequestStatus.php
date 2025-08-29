<?php

namespace App\Enums;

enum ExpenseRequestStatus: string
{
  case PENDING = 'pending';
  case APPROVED = 'approved';
  case REJECTED = 'rejected';
  case PROCESSED = 'processed';

  /**
   * Get the label for the expense request status
   */
  public function label(): string
  {
    return match($this) {
      self::PENDING => __('Pending'),
      self::APPROVED => __('Approved'),
      self::REJECTED => __('Rejected'),
      self::PROCESSED => __('Processed'),
    };
  }

  /**
   * Get the Bootstrap color class for the expense request status
   */
  public function color(): string
  {
    return match($this) {
      self::PENDING => 'warning',
      self::APPROVED => 'success',
      self::REJECTED => 'danger',
      self::PROCESSED => 'info',
    };
  }

  /**
   * Get the badge HTML for the expense request status
   */
  public function badge(): string
  {
    return '<span class="badge bg-label-' . $this->color() . '">' . $this->label() . '</span>';
  }
}
