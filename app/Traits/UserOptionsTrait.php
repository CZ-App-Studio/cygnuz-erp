<?php

namespace App\Traits;

use App\Enums\LeaveRequestStatus;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserSettings;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Assets\app\Models\Asset;
use Modules\Assets\app\Models\AssetActivity;
use Modules\Assets\app\Models\AssetAssignment;
use Modules\Assets\app\Models\AssetMaintenance;
use Modules\Calendar\app\Models\Event;
use Modules\DigitalIdCard\Models\DigitalIdCard;
use Modules\HRCore\app\Models\Attendance;
use Modules\HRCore\app\Models\BankAccount;
use Modules\HRCore\app\Models\ExpenseRequest;
use Modules\HRCore\app\Models\LeaveRequest;
use Modules\HRCore\app\Models\Shift;
use Modules\HRCore\app\Models\Team;
use Modules\HRCore\app\Models\UserAvailableLeave;
use Modules\HRCore\app\Models\Designation;
use Modules\LMS\app\Models\CourseEnrollment;
use Modules\LMS\app\Models\LessonCompletion;
use Modules\LoanManagement\App\Models\LoanRequest;
use Modules\Notes\app\Models\Note;
use Modules\Notes\app\Models\Tag;
use Modules\PaymentCollection\App\Models\PaymentCollection;
use Modules\Payroll\app\Models\PayrollAdjustment;
use Modules\SalesTarget\Models\SalesTarget;
use Modules\SiteAttendance\App\Models\Site;

trait UserOptionsTrait
{

  public function userDevice()
  {
    return $this->hasOne(UserDevice::class);
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }


  public function shift()
  {
    return $this->belongsTo(Shift::class);
  }

  public function userAvailableLeaves()
  {
    return $this->hasMany(UserAvailableLeave::class);
  }


  public function reportingTo()
  {
    return $this->belongsTo(User::class, 'reporting_to_id');
  }


  public function isOnLeave(): bool
  {
    return LeaveRequest::where('user_id', $this->id)
      ->where('status', LeaveRequestStatus::APPROVED)
      ->where('from_date', '<=', now()->toDateString())
      ->where('to_date', '>=', now()->toDateString())
      ->exists();
  }

  public function designation()
  {
    return $this->belongsTo(Designation::class);
  }

  public function getReportingToUserName()
  {
    $user = User::find($this->reporting_to_id);
    return $user ? $user->getFullName() : '';
  }


  public function userSettings()
  {
    return $this->hasOne(UserSettings::class);
  }

  public function digitalIdCard()
  {
    return $this->hasOne(DigitalIdCard::class);
  }

  public function leaveRequests()
  {
    return $this->hasMany(LeaveRequest::class);
  }

  public function expenseRequests()
  {
    return $this->hasMany(ExpenseRequest::class);
  }

  public function loanRequests()
  {
    return $this->hasMany(LoanRequest::class);
  }

  public function paymentCollections()
  {
    return $this->hasMany(PaymentCollection::class);
  }

  public function site()
  {
    return $this->belongsTo(Site::class);
  }

  public function salesTargets()
  {
    return $this->hasMany(SalesTarget::class);
  }

  public function getTodayAttendance()
  {
    return $this->attendances()
      ->whereDate('check_in_time', now()->toDateString())
      ->first();
  }

  public function attendances()
  {
    return $this->hasMany(Attendance::class);
  }


  public function bankAccount()
  {
    return $this->hasOne(BankAccount::class);
  }


  public function payrollAdjustments()
  {
    return $this->hasMany(PayrollAdjustment::class)->where('user_id', $this->id);
  }

  /**
   * The events this user is attending.
   */
  public function eventsAttending()
  {
    return $this->belongsToMany(Event::class, 'event_user', 'user_id', 'event_id')
      ->withTimestamps(); // Match the Event model's relationship
  }

  /**
   * The events created by this user.
   */
  public function eventsCreated()
  {
    return $this->hasMany(Event::class, 'created_by_id');
  }

  public function notes()
  {
    return $this->hasMany(Note::class);
  }

  /**
   * Get the tags created by this user.
   */
  public function tags()
  {
    return $this->hasMany(Tag::class);
  }


  /**
   * Get all asset assignment records for the user (history).
   */
  public function assetAssignments(): HasMany
  {
    return $this->hasMany(AssetAssignment::class)->orderBy('assigned_at', 'desc');
  }

  /**
   * Get the assets currently assigned to the user.
   */
  public function currentAssets() // Not a standard relationship, but a useful query
  {
    // Get assets through assignments where returned_at is null
    return Asset::whereHas('assignments', function ($query) {
      $query->where('user_id', $this->id)->whereNull('returned_at');
    })->get();

    // Alternative using HasManyThrough requires more setup or a dedicated "CurrentAssignment" model/view
    // return $this->hasManyThrough(Asset::class, AssetAssignment::class, 'user_id', 'id', 'id', 'asset_id')
    //            ->whereNull('asset_assignments.returned_at');
  }

  /**
   * Get maintenance records completed/logged by this user.
   */
  public function loggedMaintenances(): HasMany
  {
    return $this->hasMany(AssetMaintenance::class, 'completed_by_id');
  }

  /**
   * Get assets created by this user.
   */
  public function createdAssets(): HasMany
  {
    return $this->hasMany(Asset::class, 'created_by_id');
  }

  /**
   * Get asset activity logs performed BY this user.
   */
  public function performedAssetActivities(): HasMany
  {
    return $this->hasMany(AssetActivity::class, 'user_id')->orderBy('created_at', 'desc');
  }

  /**
   * Get asset activity logs where this user was INVOLVED (e.g., assigned asset).
   */
  public function involvedAssetActivities(): HasMany
  {
    return $this->hasMany(AssetActivity::class, 'related_user_id')->orderBy('created_at', 'desc');
  }


  /**
   * Get the course enrollments for the user.
   */
  public function courseEnrollments(): HasMany
  {
    return $this->hasMany(CourseEnrollment::class);
  }

  /**
   * Get all lesson completions for the user across all courses.
   */
  public function lessonCompletions(): HasMany
  {
    return $this->hasMany(LessonCompletion::class);
  }

  /**
   * Get bank accounts associated with the user.
   */

  public function bankAccounts(): HasMany
  {
    return $this->hasMany(BankAccount::class);
  }

  /**
   * Get desktop tracking sessions for the user.
   */
  public function desktopTrackingSessions(): HasMany
  {
    return $this->hasMany(\Modules\DesktopTracker\app\Models\DesktopTrackingSession::class);
  }

  /**
   * Get desktop activity logs for the user.
   */
  public function desktopActivityLogs(): HasMany
  {
    return $this->hasMany(\Modules\DesktopTracker\app\Models\DesktopActivityLog::class);
  }

  /**
   * Get desktop screenshots for the user.
   */
  public function desktopScreenshots(): HasMany
  {
    return $this->hasMany(\Modules\DesktopTracker\app\Models\DesktopScreenshot::class);
  }

  /**
   * Get desktop idle logs for the user.
   */
  public function desktopIdleLogs(): HasMany
  {
    return $this->hasMany(\Modules\DesktopTracker\app\Models\DesktopIdleLog::class);
  }

  /**
   * Get desktop tracking configuration for the user.
   */
  public function desktopConfiguration(): HasOne
  {
    return $this->hasOne(\Modules\DesktopTracker\app\Models\DesktopUserConfiguration::class);
  }

}
