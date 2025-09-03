<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\Attendance\AttendanceRegularizationRequest;
use App\Notifications\Attendance\LateCheckInAlert;
use App\Notifications\Attendance\MissingCheckOutAlert;
use App\Notifications\Employee\DepartmentChanged;
use App\Notifications\Employee\EmployeeOnboarding;
use App\Notifications\Expense\ExpenseRequestApproval;
use App\Notifications\Expense\NewExpenseRequest;
use App\Notifications\Holiday\HolidayAnnouncement;
use App\Notifications\Leave\LeaveBalanceUpdated;
use App\Notifications\Leave\LeaveRequestApproval;
use App\Notifications\Leave\NewLeaveRequest;
use App\Notifications\Leave\UpcomingLeaveReminder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Modules\HRCore\app\Models\AttendanceRegularization;
use Modules\HRCore\app\Models\ExpenseRequest;
use Modules\HRCore\app\Models\Holiday;
use Modules\HRCore\app\Models\LeaveRequest;
use Modules\HRCore\app\Models\UserAvailableLeave;

class HRNotificationService
{
    /**
     * Send notification for new leave request to managers/HR
     */
    public function sendNewLeaveRequestNotification(LeaveRequest $leaveRequest): void
    {
        $recipients = $this->getLeaveApprovers($leaveRequest->user);

        Notification::send($recipients, new NewLeaveRequest($leaveRequest));
    }

    /**
     * Send notification for leave request approval/rejection to employee
     */
    public function sendLeaveRequestApprovalNotification(LeaveRequest $leaveRequest, string $status): void
    {
        $leaveRequest->user->notify(new LeaveRequestApproval($leaveRequest, $status));
    }

    /**
     * Send notification for leave balance update
     */
    public function sendLeaveBalanceUpdateNotification(
        UserAvailableLeave $leaveBalance,
        string $changeType,
        float $previousBalance,
        ?string $reason = null
    ): void {
        $leaveBalance->user->notify(new LeaveBalanceUpdated(
            $leaveBalance,
            $changeType,
            $previousBalance,
            $reason
        ));
    }

    /**
     * Send upcoming leave reminders
     */
    public function sendUpcomingLeaveReminders(): void
    {
        $upcomingLeaves = LeaveRequest::with(['user', 'leaveType'])
            ->where('status', 'approved')
            ->whereBetween('from_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->get();

        foreach ($upcomingLeaves as $leaveRequest) {
            $daysUntil = now()->diffInDays($leaveRequest->from_date, false);

            // Send reminders at 7 days, 3 days, 1 day, and same day
            if (in_array($daysUntil, [7, 3, 1, 0])) {
                $leaveRequest->user->notify(new UpcomingLeaveReminder($leaveRequest, $daysUntil));
            }
        }
    }

    /**
     * Send late check-in alert
     */
    public function sendLateCheckInAlert($attendance, int $minutesLate): void
    {
        $attendance->user->notify(new LateCheckInAlert($attendance, $minutesLate));
    }

    /**
     * Send missing check-out alert
     */
    public function sendMissingCheckOutAlert($attendance): void
    {
        $attendance->user->notify(new MissingCheckOutAlert($attendance));
    }

    /**
     * Send attendance regularization request to managers
     */
    public function sendAttendanceRegularizationNotification(AttendanceRegularization $regularization): void
    {
        $recipients = $this->getAttendanceApprovers($regularization->user);

        Notification::send($recipients, new AttendanceRegularizationRequest($regularization));
    }

    /**
     * Send new expense request notification to managers
     */
    public function sendNewExpenseRequestNotification(ExpenseRequest $expenseRequest): void
    {
        $recipients = $this->getExpenseApprovers($expenseRequest->user, $expenseRequest->amount);

        Notification::send($recipients, new NewExpenseRequest($expenseRequest));
    }

    /**
     * Send expense request approval/rejection notification
     */
    public function sendExpenseRequestApprovalNotification(ExpenseRequest $expenseRequest, string $status): void
    {
        $expenseRequest->user->notify(new ExpenseRequestApproval($expenseRequest, $status));
    }

    /**
     * Send employee onboarding notification
     */
    public function sendEmployeeOnboardingNotification(User $employee, array $onboardingTasks = []): void
    {
        $employee->notify(new EmployeeOnboarding($employee, $onboardingTasks));
    }

    /**
     * Send department change notification
     */
    public function sendDepartmentChangeNotification(
        User $employee,
        $newDepartment,
        $oldDepartment = null,
        ?string $reason = null
    ): void {
        $employee->notify(new DepartmentChanged($employee, $newDepartment, $oldDepartment, $reason));
    }

    /**
     * Send holiday announcement to all applicable employees
     */
    public function sendHolidayAnnouncement(Holiday $holiday, bool $isUpcoming = true): void
    {
        $recipients = $this->getHolidayRecipients($holiday);

        Notification::send($recipients, new HolidayAnnouncement($holiday, $isUpcoming));
    }

    /**
     * Process daily notification checks (run via scheduler)
     */
    public function processDailyNotifications(): void
    {
        // Send upcoming leave reminders
        $this->sendUpcomingLeaveReminders();

        // Send holiday reminders
        $this->sendUpcomingHolidayReminders();

        // Check for missing check-outs from yesterday
        $this->checkMissingCheckOuts();
    }

    /**
     * Send upcoming holiday reminders
     */
    private function sendUpcomingHolidayReminders(): void
    {
        $upcomingHolidays = Holiday::where('is_active', true)
            ->where('send_notification', true)
            ->where('date', '>=', now()->toDateString())
            ->get();

        foreach ($upcomingHolidays as $holiday) {
            $daysUntil = now()->diffInDays($holiday->date, false);
            $reminderDays = $holiday->notification_days_before;

            if ($daysUntil == $reminderDays || $daysUntil == 1) {
                $this->sendHolidayAnnouncement($holiday, true);
            }
        }
    }

    /**
     * Check for missing check-outs from yesterday
     */
    private function checkMissingCheckOuts(): void
    {
        $yesterdayAttendances = \Modules\HRCore\app\Models\Attendance::with('user')
            ->where('date', now()->subDay()->toDateString())
            ->whereNotNull('check_in_time')
            ->whereNull('check_out_time')
            ->where('status', 'checked_in')
            ->get();

        foreach ($yesterdayAttendances as $attendance) {
            $this->sendMissingCheckOutAlert($attendance);
        }
    }

    /**
     * Get leave request approvers for a user
     */
    private function getLeaveApprovers(User $user): Collection
    {
        $approvers = collect();

        // Add direct manager
        if ($user->reporting_to_id) {
            $manager = User::find($user->reporting_to_id);
            if ($manager && $manager->hasPermissionTo('hrcore.approve-leave')) {
                $approvers->push($manager);
            }
        }

        // Add HR managers
        $hrManagers = User::permission('hrcore.approve-leave')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->get();

        return $approvers->merge($hrManagers)->unique('id');
    }

    /**
     * Get attendance regularization approvers for a user
     */
    private function getAttendanceApprovers(User $user): Collection
    {
        $approvers = collect();

        // Add direct manager
        if ($user->reporting_to_id) {
            $manager = User::find($user->reporting_to_id);
            if ($manager && $manager->hasPermissionTo('hrcore.approve-attendance-regularization')) {
                $approvers->push($manager);
            }
        }

        // Add HR managers
        $hrManagers = User::permission('hrcore.approve-attendance-regularization')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->get();

        return $approvers->merge($hrManagers)->unique('id');
    }

    /**
     * Get expense request approvers based on amount and hierarchy
     */
    private function getExpenseApprovers(User $user, float $amount): Collection
    {
        $approvers = collect();

        // Add direct manager for amounts up to certain threshold
        if ($user->reporting_to_id && $amount <= 5000) {
            $manager = User::find($user->reporting_to_id);
            if ($manager && $manager->hasPermissionTo('hrcore.approve-expense')) {
                $approvers->push($manager);
            }
        }

        // Add higher level approvers for larger amounts
        if ($amount > 5000) {
            $seniorManagers = User::permission('hrcore.approve-large-expense')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->get();

            $approvers = $approvers->merge($seniorManagers);
        }

        // Always include finance/accounting managers for expense approval
        $financeManagers = User::permission('hrcore.approve-expense')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->get();

        return $approvers->merge($financeManagers)->unique('id');
    }

    /**
     * Get recipients for holiday announcements based on applicability
     */
    private function getHolidayRecipients(Holiday $holiday): Collection
    {
        if ($holiday->applicable_for === 'all') {
            return User::where('status', 'active')->get();
        }

        $query = User::where('status', 'active');

        switch ($holiday->applicable_for) {
            case 'department':
                if ($holiday->departments) {
                    $query->whereIn('department_id', $holiday->departments);
                }
                break;

            case 'location':
                if ($holiday->locations) {
                    $query->whereIn('location', $holiday->locations);
                }
                break;

            case 'employee_type':
                if ($holiday->employee_types) {
                    $query->whereIn('employee_type', $holiday->employee_types);
                }
                break;

            case 'custom':
                if ($holiday->specific_employees) {
                    $query->whereIn('id', $holiday->specific_employees);
                }
                break;
        }

        return $query->get();
    }

    /**
     * Get user's notification preferences for HR module
     */
    public function getUserHRNotificationPreferences(User $user): array
    {
        $preferences = $user->notification_preferences->preferences ?? [];
        $hrPrefs = $preferences['hrcore'] ?? [];

        // Default preferences
        $defaults = [
            'leave' => [
                'email' => true,
                'push' => true,
                'reminder_email' => true,
                'reminder_push' => true,
                'balance_updates_email' => false,
                'balance_updates_push' => false,
            ],
            'attendance' => [
                'late_checkin_email' => false,
                'late_checkin_push' => true,
                'missing_checkout_email' => true,
                'missing_checkout_push' => true,
                'regularization_email' => true,
                'regularization_push' => true,
            ],
            'expense' => [
                'email' => true,
                'push' => true,
            ],
            'employee' => [
                'onboarding_push' => true,
                'profile_changes_email' => true,
                'profile_changes_push' => true,
            ],
            'holiday' => [
                'announcement_email' => true,
                'announcement_push' => true,
            ],
        ];

        return array_merge_recursive($defaults, $hrPrefs);
    }
}
