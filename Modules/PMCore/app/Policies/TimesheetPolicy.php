<?php

namespace Modules\PMCore\app\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\PMCore\app\Models\Timesheet;

class TimesheetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any timesheets.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('pmcore.view-timesheets') || $user->can('pmcore.view-own-timesheets');
    }

    /**
     * Determine whether the user can view the timesheet.
     */
    public function view(User $user, Timesheet $timesheet): bool
    {
        if ($user->can('pmcore.view-timesheets')) {
            return true;
        }

        if ($user->can('pmcore.view-own-timesheets')) {
            return $timesheet->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create timesheets.
     */
    public function create(User $user): bool
    {
        return $user->can('pmcore.create-timesheet');
    }

    /**
     * Determine whether the user can update the timesheet.
     */
    public function update(User $user, Timesheet $timesheet): bool
    {
        // Can't edit approved or rejected timesheets
        if (in_array($timesheet->status, ['approved', 'rejected'])) {
            return false;
        }

        if ($user->can('pmcore.edit-timesheet')) {
            return true;
        }

        if ($user->can('pmcore.edit-own-timesheet')) {
            return $timesheet->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the timesheet.
     */
    public function delete(User $user, Timesheet $timesheet): bool
    {
        // Can't delete approved timesheets
        if ($timesheet->status === 'approved') {
            return false;
        }

        return $user->can('pmcore.delete-timesheet');
    }

    /**
     * Determine whether the user can submit the timesheet.
     */
    public function submit(User $user, Timesheet $timesheet): bool
    {
        return $user->can('pmcore.submit-timesheet') &&
               $timesheet->user_id === $user->id &&
               $timesheet->status === 'draft';
    }

    /**
     * Determine whether the user can approve the timesheet.
     */
    public function approve(User $user, Timesheet $timesheet): bool
    {
        return $user->can('pmcore.approve-timesheet') &&
               $timesheet->status === 'submitted' &&
               $timesheet->user_id !== $user->id; // Can't approve own timesheets
    }

    /**
     * Determine whether the user can reject the timesheet.
     */
    public function reject(User $user, Timesheet $timesheet): bool
    {
        return $user->can('pmcore.reject-timesheet') &&
               $timesheet->status === 'submitted' &&
               $timesheet->user_id !== $user->id; // Can't reject own timesheets
    }

    /**
     * Determine whether the user can export timesheets.
     */
    public function export(User $user): bool
    {
        return $user->can('pmcore.export-timesheets');
    }
}
