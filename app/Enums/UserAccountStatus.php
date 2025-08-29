<?php

namespace App\Enums;

enum UserAccountStatus: string
{
  case ACTIVE = 'active';
  case INACTIVE = 'inactive';
  case DELETED = 'deleted';
  case ONBOARDING = 'onboarding';
  case RETIRED = 'retired';
  case RELIEVED = 'relieved';
  case SUSPENDED = 'suspended';
  case PENDING = 'pending';
  case REJECTED = 'rejected';
  case APPROVED = 'approved';
  case BLOCKED = 'blocked';
  case INVITED = 'invited';
  case REGISTERED = 'registered';
  case TERMINATED = 'terminated';
  case PROBATION_FAILED = 'probation_failed';
  case PROBATION = 'probation';
  case RESIGNED = 'resigned';
  case CONFIRMED = 'confirmed';

  /**
   * Get the label for the status
   */
  public function label(): string
  {
    return match($this) {
      self::ACTIVE => 'Active',
      self::INACTIVE => 'Inactive',
      self::DELETED => 'Deleted',
      self::ONBOARDING => 'Onboarding',
      self::RETIRED => 'Retired',
      self::RELIEVED => 'Relieved',
      self::SUSPENDED => 'Suspended',
      self::PENDING => 'Pending',
      self::REJECTED => 'Rejected',
      self::APPROVED => 'Approved',
      self::BLOCKED => 'Blocked',
      self::INVITED => 'Invited',
      self::REGISTERED => 'Registered',
      self::TERMINATED => 'Terminated',
      self::PROBATION_FAILED => 'Probation Failed',
      self::PROBATION => 'Probation',
      self::RESIGNED => 'Resigned',
      self::CONFIRMED => 'Confirmed',
    };
  }

  /**
   * Get the Bootstrap color class for the status
   */
  public function color(): string
  {
    return match($this) {
      self::ACTIVE, self::APPROVED, self::CONFIRMED => 'success',
      self::INACTIVE, self::PENDING => 'warning',
      self::DELETED, self::RELIEVED, self::TERMINATED, self::REJECTED, self::BLOCKED, self::SUSPENDED, self::RESIGNED, self::PROBATION_FAILED => 'danger',
      self::ONBOARDING, self::INVITED, self::PROBATION => 'info',
      self::RETIRED, self::REGISTERED => 'secondary',
    };
  }

  /**
   * Get the badge HTML for the status
   */
  public function badge(): string
  {
    return '<span class="badge bg-label-' . $this->color() . '">' . $this->label() . '</span>';
  }
}
