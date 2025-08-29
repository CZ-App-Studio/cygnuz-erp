<?php

namespace App\Http\Controllers;

use App\Enums\LeaveRequestStatus;
use App\Enums\UserAccountStatus;
use App\Models\User;
use Modules\DocumentManagement\App\Models\DocumentRequest;
use Modules\HRCore\app\Models\Attendance;
use Modules\HRCore\app\Models\ExpenseRequest;
use Modules\HRCore\app\Models\LeaveRequest;
use Modules\LoanManagement\App\Models\LoanRequest;
use Modules\TaskSystem\App\Models\Task;

class UserDashboardController extends Controller
{
    public function index()
    {


      $totalUser = User::count();
      $active = User::where('status', UserAccountStatus::ACTIVE)->count();
      $presentUsersCount = Attendance::whereDate('created_at', now())->count();
      $presentUsersCountLastWeek = Attendance::whereBetween('created_at', [now()->startOfWeek()->subWeek(), now()->endOfWeek()->subWeek()])
        ->where('check_out_time', '!=', null)
        ->get()
        ->sum(function ($attendance) {
          return $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
        });

      $thisWeekWorkingHours = Attendance::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
        ->where('check_out_time', '!=', null)
        ->get()
        ->sum(function ($attendance) {
          return $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
        });

      $todayHours = Attendance::whereDate('created_at', now())
        ->where('check_out_time', '!=', null)
        ->get()
        ->sum(function ($attendance) {
          return $attendance->check_in_time->diffInMinutes($attendance->check_out_time);
        });

      $onLeaveUsersCount = LeaveRequest::whereDate('from_date', now())
        ->where('status', LeaveRequestStatus::APPROVED)
        ->count();


        return view('tenant.users.dashboard.index', [
          'totalUser' => $totalUser,
          'activeEmployees' => $active,
          'active' => $active,
          'presentUsersCount' => $presentUsersCount,
          'pendingLeaveRequests' => LeaveRequest::where('status', 'pending')->count(),
          'pendingExpenseRequests' => ExpenseRequest::where('status', 'pending')->count(),
          'pendingDocumentRequests' => DocumentRequest::where('status', 'pending')->count(),
          'pendingLoanRequests' => LoanRequest::where('status', 'pending')->count(),
          'thisWeekWorkingHours' => round($thisWeekWorkingHours, 2),
          'todayHours' => round($todayHours, 2),
          'tasks' => Task::where('status', 'new')->count(),
          'onGoingTasks' => Task::where('status', 'in_progress')->count(),
          'todayPresentUsers' => $presentUsersCount,
          'todayAbsentUsers' => $active - $presentUsersCount,
          'presentUsersCountLastWeek' => $presentUsersCountLastWeek,
          'absentUsersCountLastWeek' => $active - $presentUsersCountLastWeek,
          'onLeaveUsersCount' => $onLeaveUsersCount,
        ]);
    }
}
