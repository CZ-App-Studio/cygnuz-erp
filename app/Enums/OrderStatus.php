<?php

namespace App\Enums;

enum OrderStatus: string
{
  case PENDING = 'pending';
  case PROCESSING = 'processing';
  case COMPLETED = 'completed';
  case CANCELLED = 'cancelled';
  case REFUNDED = 'refunded';
  case FAILED = 'failed';

  /**
   * Get the label for the order status
   */
  public function label(): string
  {
    return match($this) {
      self::PENDING => __('Pending'),
      self::PROCESSING => __('Processing'),
      self::COMPLETED => __('Completed'),
      self::CANCELLED => __('Cancelled'),
      self::REFUNDED => __('Refunded'),
      self::FAILED => __('Failed'),
    };
  }

  /**
   * Get the Bootstrap color class for the order status
   */
  public function color(): string
  {
    return match($this) {
      self::PENDING => 'warning',
      self::PROCESSING => 'info',
      self::COMPLETED => 'success',
      self::CANCELLED => 'secondary',
      self::REFUNDED => 'primary',
      self::FAILED => 'danger',
    };
  }

  /**
   * Get the badge HTML for the order status
   */
  public function badge(): string
  {
    return '<span class="badge bg-label-' . $this->color() . '">' . $this->label() . '</span>';
  }
}
