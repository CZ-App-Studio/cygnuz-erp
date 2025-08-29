<?php

namespace App\Enums;

enum TaskStatus: string
{
  case NEW = 'new';
  case IN_PROGRESS = 'in_progress';
  case COMPLETED = 'completed';
  case HOLD = 'hold';
  case CANCELLED = 'cancelled';
  case DELETED = 'deleted';

  /**
   * Get the label for the task status
   */
  public function label(): string
  {
    return match($this) {
      self::NEW => __('New'),
      self::IN_PROGRESS => __('In Progress'),
      self::COMPLETED => __('Completed'),
      self::HOLD => __('On Hold'),
      self::CANCELLED => __('Cancelled'),
      self::DELETED => __('Deleted'),
    };
  }

  /**
   * Get the Bootstrap color class for the task status
   */
  public function color(): string
  {
    return match($this) {
      self::NEW => 'primary',
      self::IN_PROGRESS => 'info',
      self::COMPLETED => 'success',
      self::HOLD => 'warning',
      self::CANCELLED => 'secondary',
      self::DELETED => 'danger',
    };
  }

  /**
   * Get the badge HTML for the task status
   */
  public function badge(): string
  {
    return '<span class="badge bg-label-' . $this->color() . '">' . $this->label() . '</span>';
  }
}
