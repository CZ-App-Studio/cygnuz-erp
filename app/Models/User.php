<?php

namespace App\Models;

use App\Enums\UserAccountStatus;
use App\Traits\UserActionsTrait;
use App\Traits\UserOptionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\FileManagerCore\Contracts\FileManagerInterface;
use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Traits\HasFiles;
use Modules\FileManagerCore\Traits\TracksStorage;
// use Modules\LocationManagement\app\Traits\HasLocation; // Module not available
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements AuditableContract, JWTSubject
{
    use Auditable, HasApiTokens, HasFactory, HasFiles, HasRoles, Notifiable, SoftDeletes, TracksStorage, UserActionsTrait, UserOptionsTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'status',
        'dob',
        'gender',
        'profile_picture',
        'alternate_number',
        'cover_picture',
        'email',
        'email_verified_at',
        'phone_verified_at',
        'password',
        'remember_token',
        'language',
        'delete_request_at',
        'designation_id',
        'shift_id',
        'delete_request_reason',
        'team_id',
        'code',
        'date_of_joining',
        'anniversary_date',
        'available_leave_count',
        'relieved_at',
        'relieved_reason',
        'retired_at',
        'retired_reason',
        'is_customer',
        'exit_date',
        'exit_reason',
        'termination_type',
        'last_working_day',
        'is_eligible_for_rehire',
        'notice_period_days',
        'probation_period_months',
        'probation_end_date',
        'probation_confirmed_at',
        'is_probation_extended',
        'probation_remarks',
        'reporting_to_id',
        'attendance_type',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function getUserForProfile()
    {
        return [
            'name' => $this->getFullName(),
            'code' => $this->code,
            'initials' => $this->getInitials(),
            'profile_picture' => $this->getProfilePicture(),
        ];
    }

    /**
     * Check if the user is currently under probation.
     */
    public function isUnderProbation(): bool
    {
        return $this->status === UserAccountStatus::ACTIVE && // Must be active
          ! is_null($this->probation_end_date) && // Must have an end date
          is_null($this->probation_confirmed_at) && // Must not be confirmed yet
          Carbon::parse($this->probation_end_date)->isFuture(); // End date must be in the future
    }

    /**
     * Get a display string for the user's probation status.
     */
    public function getProbationStatusDisplayAttribute(): string
    {
        if ($this->status !== UserAccountStatus::ACTIVE || is_null($this->probation_end_date)) {
            return 'Not Applicable';
        }
        if (! is_null($this->probation_confirmed_at)) {
            return 'Completed on '.Carbon::parse($this->probation_confirmed_at)->format('M d, Y');
        }
        if ($this->isUnderProbation()) {
            $statusText = 'Active until '.Carbon::parse($this->probation_end_date)->format('M d, Y');
            if ($this->is_probation_extended) {
                $statusText .= ' (Extended)';
            }

            return $statusText;
        }
        if (Carbon::parse($this->probation_end_date)->isPast()) {
            // Past due date but not confirmed - needs action
            return 'Pending Confirmation (Ended '.Carbon::parse($this->probation_end_date)->format('M d, Y').')';
        }

        return 'Unknown'; // Should not happen often
    }
    // --- End Probation Accessors ---

    public function getFullName()
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function getInitials(): string
    {
        return strtoupper(substr($this->first_name, 0, 1).substr($this->last_name, 0, 1));
    }

    public function getProfilePicture()
    {
        // Try to get profile picture from FileManagerCore first
        $profilePictureFile = $this->getProfilePictureFile();
        if ($profilePictureFile) {
            return app(FileManagerInterface::class)->getFileUrl($profilePictureFile);
        }

        return null;
    }

    /**
     * Get the profile picture file from FileManagerCore
     */
    public function getProfilePictureFile(): ?\Modules\FileManagerCore\Models\File
    {
        return $this->fileByType(FileType::EMPLOYEE_PROFILE_PICTURE);
    }

    /**
     * Get profile picture URL using FileManagerCore
     */
    public function getProfilePictureUrl(): ?string
    {
        $profilePicture = $this->getProfilePictureFile();

        if (! $profilePicture) {
            return null;
        }

        return app(FileManagerInterface::class)->getFileUrl($profilePicture);
    }

    /**
     * Check if user has profile picture in FileManagerCore
     */
    public function hasProfilePicture(): bool
    {
        return $this->hasFileOfType(FileType::EMPLOYEE_PROFILE_PICTURE);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Specifies the user's FCM tokens
     *
     * @return string|array
     */
    public function fcmToken()
    {
        return $this->getDeviceToken();
    }

    public function getDeviceToken()
    {
        $userDevice = UserDevice::where('user_id', $this->id)->first();

        return $userDevice?->token;
    }

    public function hasActivePlan(): bool
    {
        return $this->plan_id != null && $this->plan_expired_date >= now()->toDateString();
    }

    // Get the user's full name as the "name" attribute
    public function getNameAttribute()
    {
        return $this->getFullName();
    }

    public function getFullNameAttribute()
    {
        return $this->getFullName();
    }

    // Tenant Specific

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'dob' => 'date',
            'probation_end_date' => 'date',
            'status' => UserAccountStatus::class,
        ];
    }

    /**
     * Get leave balance for a specific leave type
     */
    public function getLeaveBalance($leaveTypeId)
    {
        $availableLeave = \Modules\HRCore\app\Models\UserAvailableLeave::where('user_id', $this->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', date('Y'))
            ->first();

        if ($availableLeave) {
            return $availableLeave->available_leaves;
        }

        // Alternative: Calculate from accruals if using accrual system
        return \Modules\HRCore\app\Models\LeaveAccrual::getCurrentBalance($this->id, $leaveTypeId);
    }

    /**
     * Get all leave balances
     */
    public function getLeaveBalances()
    {
        return \Modules\HRCore\app\Models\UserAvailableLeave::where('user_id', $this->id)
            ->with('leaveType')
            ->get();
    }

    /**
     * Get leave requests
     */
    public function leaveRequests()
    {
        return $this->hasMany(\Modules\HRCore\app\Models\LeaveRequest::class, 'user_id');
    }

    public function resourceAllocations()
    {
        if (class_exists('\\Modules\\PMCore\\app\\Models\\ResourceAllocation')) {
            return $this->hasMany(\Modules\PMCore\app\Models\ResourceAllocation::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's timesheets
     */
    public function timesheets()
    {
        if (class_exists('\\Modules\\PMCore\\app\\Models\\Timesheet')) {
            return $this->hasMany(\Modules\PMCore\app\Models\Timesheet::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get available compensatory offs
     */
    public function getAvailableCompOffs()
    {
        return \Modules\HRCore\app\Models\CompensatoryOff::where('user_id', $this->id)
            ->available()
            ->sum('comp_off_days');
    }

    /**
     * Get employee lifecycle states
     */
    public function lifecycleStates()
    {
        return $this->hasMany(\Modules\HRCore\app\Models\EmployeeLifecycleState::class, 'user_id');
    }

    /**
     * Get employee histories
     */
    public function employeeHistories()
    {
        return $this->hasMany(\Modules\HRCore\app\Models\EmployeeHistory::class, 'user_id');
    }

    /**
     * Get current lifecycle state
     */
    public function currentLifecycleState()
    {
        return $this->hasOne(\Modules\HRCore\app\Models\EmployeeLifecycleState::class, 'user_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get user's designation
     */
    public function designation()
    {
        if (class_exists('\\Modules\\HRCore\\app\\Models\\Designation')) {
            return $this->belongsTo(\Modules\HRCore\app\Models\Designation::class, 'designation_id');
        }

        return $this->belongsTo(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's team
     */
    public function team()
    {
        if (class_exists('\\Modules\\HRCore\\app\\Models\\Team')) {
            return $this->belongsTo(\Modules\HRCore\app\Models\Team::class, 'team_id');
        }

        return $this->belongsTo(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's shift
     */
    public function shift()
    {
        if (class_exists('\\Modules\\HRCore\\app\\Models\\Shift')) {
            return $this->belongsTo(\Modules\HRCore\app\Models\Shift::class, 'shift_id');
        }

        return $this->belongsTo(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's manager
     */
    public function manager()
    {
        return $this->belongsTo(\App\Models\User::class, 'reporting_manager_id');
    }

    /**
     * Get user's reporting to (manager)
     */
    public function reportingTo()
    {
        return $this->belongsTo(\App\Models\User::class, 'reporting_to_id');
    }

    /**
     * Get user's department through designation
     */
    public function department()
    {
        if (class_exists('\\Modules\\HRCore\\app\\Models\\Department')) {
            return $this->hasOneThrough(
                \Modules\HRCore\app\Models\Department::class,
                \Modules\HRCore\app\Models\Designation::class,
                'id', // Foreign key on designations table
                'id', // Foreign key on departments table
                'designation_id', // Local key on users table
                'department_id' // Local key on designations table
            );
        }

        return $this->belongsTo(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's salary information
     */
    public function employeeSalary()
    {
        if (class_exists('\\Modules\\Payroll\\app\\Models\\EmployeeSalary')) {
            return $this->hasOne(\Modules\Payroll\app\Models\EmployeeSalary::class, 'user_id');
        }

        return $this->hasOne(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get all user's salary records
     */
    public function employeeSalaries()
    {
        if (class_exists('\\Modules\\Payroll\\app\\Models\\EmployeeSalary')) {
            return $this->hasMany(\Modules\Payroll\app\Models\EmployeeSalary::class, 'user_id');
        }

        return $this->hasMany(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's current active salary
     */
    public function activeSalary()
    {
        if (class_exists('\\Modules\\Payroll\\app\\Models\\EmployeeSalary')) {
            return $this->hasOne(\Modules\Payroll\app\Models\EmployeeSalary::class, 'user_id')
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', now());
                })
                ->where('effective_from', '<=', now());
        }

        return $this->hasOne(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get user's two-factor authentication settings
     */
    public function twoFactorAuth()
    {
        if (class_exists(\Modules\TwoFactorAuth\app\Models\UserTwoFactorAuth::class)) {
            return $this->hasOne(\Modules\TwoFactorAuth\app\Models\UserTwoFactorAuth::class, 'user_id');
        }

        return $this->hasOne(\App\Models\User::class, 'id')->whereRaw('1=0'); // Empty relation
    }

    /**
     * Get the user's notification preferences
     */
    public function notification_preferences()
    {
        return $this->hasOne(\App\Models\NotificationPreference::class, 'user_id');
    }

    /**
     * Check if user has 2FA enabled
     */
    public function hasTwoFactorEnabled(): bool
    {
        if (! class_exists(\Modules\TwoFactorAuth\app\Models\UserTwoFactorAuth::class)) {
            return false;
        }

        $twoFactor = $this->twoFactorAuth;

        return $twoFactor && $twoFactor->isEnabled();
    }
}
