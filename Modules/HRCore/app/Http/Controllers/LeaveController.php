<?php

namespace Modules\HRCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Enums\LeaveRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\Leave\LeaveRequestApproval;
use App\Services\HRNotificationService;
use Constants;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\FileManagerCore\Contracts\FileManagerInterface;
use Modules\FileManagerCore\DTO\FileUploadRequest;
use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Enums\FileVisibility;
use Modules\HRCore\app\Models\LeaveRequest;
use Modules\HRCore\app\Models\LeaveType;
use Yajra\DataTables\Facades\DataTables;
use App\Services\Settings\ModuleSettingsService;

class LeaveController extends Controller
{
    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        $this->middleware('permission:hrcore.view-leaves|hrcore.view-own-leaves')->only(['index', 'indexAjax', 'getListAjax']);
        $this->middleware('permission:hrcore.view-leaves|hrcore.view-own-leaves')->only(['show', 'showPage', 'getByIdAjax']);
        $this->middleware('permission:hrcore.create-leave')->only(['create', 'store', 'applyLeave']);
        $this->middleware('permission:hrcore.edit-leave')->only(['edit', 'update']);
        $this->middleware('permission:hrcore.delete-leave')->only(['destroy']);
        $this->middleware('permission:hrcore.approve-leave|hrcore.reject-leave|hrcore.cancel-leave')->only(['actionAjax', 'approve', 'reject', 'cancel']);
        $this->middleware('permission:hrcore.view-leave-balances|hrcore.view-own-leaves')->only(['myBalance', 'getLeaveBalanceForType']);
        $this->middleware('permission:hrcore.view-team-leaves')->only(['teamCalendar']);
    }

    /**
     * Calculate total leave days based on settings
     */
    protected function calculateLeaveDays($fromDate, $toDate, $isHalfDay = false): float
    {
        $settingsService = app(ModuleSettingsService::class);
        $includeWeekends = $settingsService->get('HRCore', 'weekend_included_in_leave', false);
        $includeHolidays = $settingsService->get('HRCore', 'holidays_included_in_leave', false);
        
        $startDate = \Carbon\Carbon::parse($fromDate);
        $endDate = \Carbon\Carbon::parse($toDate);
        
        if ($isHalfDay) {
            return 0.5;
        }
        
        $days = 0;
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $isWeekend = $currentDate->isWeekend();
            $isHoliday = \Modules\HRCore\app\Models\Holiday::whereDate('date', $currentDate)->exists();
            
            // Skip weekends if not included
            if (!$includeWeekends && $isWeekend) {
                $currentDate->addDay();
                continue;
            }
            
            // Skip holidays if not included
            if (!$includeHolidays && $isHoliday) {
                $currentDate->addDay();
                continue;
            }
            
            $days++;
            $currentDate->addDay();
        }
        
        return $days;
    }

    public function index()
    {
        $employees = User::where('status', 'active')->get();
        $leaveTypes = LeaveType::where('status', 'active')->get();

        return view('hrcore::leave.index', compact('employees', 'leaveTypes'));
    }

    /**
     * Get leave balance for a specific leave type
     */
    public function getLeaveBalanceForType($leaveTypeId)
    {
        $user = auth()->user();
        $currentYear = date('Y');

        // Get leave type details
        $leaveType = LeaveType::find($leaveTypeId);

        // Get user's available leave from the correct table
        $userLeaveBalance = \Modules\HRCore\app\Models\UserAvailableLeave::where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $currentYear)
            ->first();

        // Calculate used and pending leaves for this type
        $usedLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'approved')
            ->whereYear('from_date', $currentYear)
            ->sum('total_days');

        $pendingLeaves = LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $leaveTypeId)
            ->where('status', 'pending')
            ->whereYear('from_date', $currentYear)
            ->sum('total_days');

        // Calculate values
        $entitled = $userLeaveBalance ? $userLeaveBalance->entitled_leaves : ($leaveType ? $leaveType->days_allowed : 0);
        $carriedForward = $userLeaveBalance ? $userLeaveBalance->carried_forward_leaves : 0;
        $additional = $userLeaveBalance ? $userLeaveBalance->additional_leaves : 0;
        $totalAllowed = $entitled + $carriedForward + $additional;
        $available = $userLeaveBalance ? $userLeaveBalance->available_leaves : ($totalAllowed - $usedLeaves);

        return response()->json([
            'success' => true,
            'balance' => [
                'total' => $totalAllowed,
                'entitled' => $entitled,
                'carried_forward' => $carriedForward,
                'additional' => $additional,
                'used' => $usedLeaves,
                'pending' => $pendingLeaves,
                'available' => $available,
            ],
        ]);
    }

    /**
     * Show leave application form for employees
     */
    public function applyLeave()
    {
        $leaveTypes = LeaveType::where('status', 'active')->get();
        $user = auth()->user();
        $currentYear = date('Y');

        // Get user's leave balances for all leave types
        $userLeaveBalances = \Modules\HRCore\app\Models\UserAvailableLeave::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->get()
            ->keyBy('leave_type_id');

        // Add balance info to each leave type
        foreach ($leaveTypes as $leaveType) {
            $balance = $userLeaveBalances->get($leaveType->id);
            if ($balance) {
                $leaveType->user_entitled = $balance->entitled_leaves;
                $leaveType->user_carried = $balance->carried_forward_leaves;
                $leaveType->user_additional = $balance->additional_leaves;
                $leaveType->user_used = $balance->used_leaves;
                $leaveType->user_available = $balance->available_leaves;
            } else {
                $leaveType->user_entitled = $leaveType->days_allowed;
                $leaveType->user_carried = 0;
                $leaveType->user_additional = 0;
                $leaveType->user_used = 0;
                $leaveType->user_available = $leaveType->days_allowed;
            }
        }

        return view('hrcore::leave.apply', compact('leaveTypes', 'user'));
    }

    /**
     * Show employee's leave balance
     */
    public function myBalance()
    {
        $user = auth()->user();
        $leaveTypes = LeaveType::where('status', 'active')->get();
        $currentYear = date('Y');

        // Get user's leave balances from the correct table
        $balances = \Modules\HRCore\app\Models\UserAvailableLeave::where('user_id', $user->id)
            ->where('year', $currentYear)
            ->with('leaveType')
            ->get()
            ->keyBy('leave_type_id');

        // Add balance info to each leave type
        foreach ($leaveTypes as $leaveType) {
            $balance = $balances->get($leaveType->id);
            if ($balance) {
                $leaveType->entitled_leaves = $balance->entitled_leaves;
                $leaveType->carried_forward_leaves = $balance->carried_forward_leaves;
                $leaveType->additional_leaves = $balance->additional_leaves;
                $leaveType->used_leaves = $balance->used_leaves;
                $leaveType->available_leaves = $balance->available_leaves;
            } else {
                $leaveType->entitled_leaves = $leaveType->days_allowed;
                $leaveType->carried_forward_leaves = 0;
                $leaveType->additional_leaves = 0;
                $leaveType->used_leaves = 0;
                $leaveType->available_leaves = $leaveType->days_allowed;
            }
        }

        return view('hrcore::leave.balance', compact('user', 'leaveTypes', 'balances'));
    }

    /**
     * Show team calendar view for leaves
     */
    public function teamCalendar()
    {
        $user = auth()->user();

        // Get the user's department through their designation
        $userDepartmentId = $user->designation ? $user->designation->department_id : null;

        // Get team members from the same department
        $teamMembers = User::whereHas('designation', function ($query) use ($userDepartmentId) {
            $query->where('department_id', $userDepartmentId);
        })
            ->where('status', 'active')
            ->get();

        $leaves = LeaveRequest::whereIn('user_id', $teamMembers->pluck('id'))
            ->whereIn('status', ['approved', 'pending'])
            ->where('from_date', '>=', now()->subMonth())
            ->where('to_date', '<=', now()->addMonths(2))
            ->with(['user', 'leaveType'])
            ->get();

        // Get leave types for filters
        $leaveTypes = LeaveType::where('status', 'active')->get();

        return view('hrcore::leave.team-calendar', compact('user', 'userDepartmentId', 'teamMembers', 'leaves', 'leaveTypes'));
    }

    /**
     * Show the form for creating a new leave request
     */
    public function create()
    {
        $employees = User::where('status', 'active')->get();
        $leaveTypes = LeaveType::where('status', 'active')->get();

        return view('hrcore::leave.create', compact('employees', 'leaveTypes'));
    }

    /**
     * Store a newly created leave request
     */
    public function store(Request $request)
    {
        // Get settings for leave validation
        $settingsService = app(ModuleSettingsService::class);
        $minAdvanceNoticeDays = (int) $settingsService->get('HRCore', 'min_advance_notice_days', '0');
        // If minAdvanceNoticeDays is 0, allow today. Otherwise, add the days
        $minDate = $minAdvanceNoticeDays > 0 ? now()->addDays($minAdvanceNoticeDays)->toDateString() : now()->toDateString();
        
        $validated = $request->validate([
            'user_id' => auth()->user()->can('hrcore.create-leave-for-others') ? 'required|exists:users,id' : 'nullable',
            'leave_type_id' => 'required|exists:leave_types,id',
            'from_date' => 'required|date|after_or_equal:' . $minDate,
            'to_date' => 'required|date|after_or_equal:from_date',
            'is_half_day' => 'nullable|boolean',
            'half_day_type' => 'nullable|in:first_half,second_half',
            'total_days' => 'nullable|numeric',
            'user_notes' => 'required|string|max:500',
            'emergency_contact' => 'nullable|string|max:100',
            'emergency_phone' => 'nullable|string|max:50',
            'is_abroad' => 'nullable|boolean',
            'abroad_location' => 'nullable|required_if:is_abroad,1|string|max:200',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Set user_id if not provided (for self leave request)
            if (! isset($validated['user_id'])) {
                $validated['user_id'] = auth()->id();
            }

            // Calculate total days based on settings
            $totalDays = $this->calculateLeaveDays(
                $validated['from_date'],
                $validated['to_date'],
                $validated['is_half_day'] ?? false
            );
            $validated['total_days'] = $totalDays;

            // Create leave request
            $leaveRequest = new LeaveRequest($validated);

            // Handle document upload using FileManagerCore
            if ($request->hasFile('document')) {
                try {
                    if (app()->bound(FileManagerInterface::class)) {
                        $fileManager = app(FileManagerInterface::class);
                        $file = $request->file('document');

                        // Create upload request DTO
                        $uploadRequest = new FileUploadRequest(
                            file: $file,
                            type: FileType::LEAVE_DOCUMENT,
                            visibility: FileVisibility::PRIVATE,
                            attachableType: LeaveRequest::class,
                            attachableId: null, // Will be set after save
                            userId: auth()->id()
                        );

                        // Upload file
                        $uploadedFile = $fileManager->uploadFile($uploadRequest);

                        if ($uploadedFile) {
                            // Store the filename for backward compatibility
                            $leaveRequest->document = $uploadedFile->filename;
                        }
                    } else {
                        // Fallback to simple storage if FileManagerCore is not available
                        $file = $request->file('document');
                        $fileName = time().'_'.$file->getClientOriginalName();
                        $path = $file->storeAs('public/uploads/leaverequestdocuments', $fileName);
                        $leaveRequest->document = $fileName;
                    }
                } catch (\Exception $e) {
                    Log::error('Document upload failed: '.$e->getMessage());
                    // Continue without document - it's optional
                }
            }

            $leaveRequest->save();

            // Update file association if using FileManagerCore
            if (isset($uploadedFile) && $uploadedFile && $leaveRequest->id) {
                $uploadedFile->update([
                    'attachable_id' => $leaveRequest->id,
                ]);
            }

            // Check for overlapping leaves
            if ($leaveRequest->hasOverlappingLeave()) {
                \Illuminate\Support\Facades\DB::rollBack();

                return Error::response(__('You already have a leave request for the selected dates.'));
            }

            // Set status to pending (simplified - remove submitForApproval which might be causing issues)
            $leaveRequest->status = 'pending';
            $leaveRequest->save();

            \Illuminate\Support\Facades\DB::commit();

            // Send new leave request notification to approvers
            try {
                $notificationService = app(HRNotificationService::class);
                $notificationService->sendNewLeaveRequestNotification($leaveRequest);
            } catch (\Exception $e) {
                Log::error('Failed to send leave request notification: ' . $e->getMessage());
                // Don't fail the request if notification fails
            }

            return Success::response(__('Leave request submitted successfully!'));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Failed to create leave request: '.$e->getMessage());

            return Error::response(__('Failed to submit leave request. Please try again.'));
        }
    }

    /**
     * Get leave requests for DataTable via AJAX
     */
    public function indexAjax(Request $request)
    {
        $query = LeaveRequest::query()
            ->with(['user', 'leaveType']);

        // Apply permission-based filtering
        if (auth()->user()->can('hrcore.view-own-leaves') && ! auth()->user()->can('hrcore.view-leaves')) {
            // User can only see their own leaves
            $query->where('user_id', auth()->id());
        } elseif (auth()->user()->hasRole('team_leader')) {
            // Team leader can see their team's leaves
            $teamMembers = User::where('reporting_to_id', auth()->id())->pluck('id');
            $query->whereIn('user_id', $teamMembers->push(auth()->id()));
        }

        // Apply filters
        if ($request->filled('employeeFilter')) {
            $query->where('user_id', $request->employeeFilter);
        }

        if ($request->filled('leaveTypeFilter')) {
            $query->where('leave_type_id', $request->leaveTypeFilter);
        }

        if ($request->filled('dateFilter')) {
            $query->whereDate('created_at', $request->dateFilter);
        }

        if ($request->filled('statusFilter')) {
            $query->where('status', $request->statusFilter);
        }

        // Handle search
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                // Search in actual database columns
                $q->where('leave_requests.id', 'like', "%{$searchValue}%")
                  ->orWhere('leave_requests.status', 'like', "%{$searchValue}%")
                  ->orWhere('leave_requests.user_notes', 'like', "%{$searchValue}%")
                  // Search in related tables - user names
                  ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                      $userQuery->where('first_name', 'like', "%{$searchValue}%")
                                ->orWhere('last_name', 'like', "%{$searchValue}%")
                                ->orWhere('code', 'like', "%{$searchValue}%")
                                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$searchValue}%");
                  })
                  // Search in leave type name
                  ->orWhereHas('leaveType', function ($typeQuery) use ($searchValue) {
                      $typeQuery->where('name', 'like', "%{$searchValue}%");
                  });
            });
        }

        return DataTables::of($query)
            ->addColumn('user', function ($leave) {
                return view('components.datatable-user', [
                    'user' => $leave->user,
                    'showCode' => true,
                    'linkRoute' => 'hrcore.employees.show',
                ])->render();
            })
            ->addColumn('leave_type', function ($leave) {
                return $leave->leaveType->name;
            })
            ->addColumn('leave_dates', function ($leave) {
                $fromDate = $leave->from_date->format('d M Y');
                $toDate = $leave->to_date->format('d M Y');

                return "<div class='d-flex flex-column'>
                  <span>{$fromDate}</span>
                  <small class='text-muted'>to</small>
                  <span>{$toDate}</span>
                </div>";
            })
            ->editColumn('status', function ($leave) {
                if ($leave->status instanceof LeaveRequestStatus) {
                    return $leave->status->badge();
                }

                try {
                    $status = LeaveRequestStatus::from($leave->status);

                    return $status->badge();
                } catch (\ValueError $e) {
                    return '<span class="badge bg-label-secondary">Unknown</span>';
                }
            })
            ->addColumn('document', function ($leave) {
                if ($leave->document) {
                    $url = asset('storage/'.Constants::BaseFolderLeaveRequestDocument.$leave->document);

                    return "<a href='{$url}' class='glightbox'>
                    <img src='{$url}' alt='Document' height='50'/>
                  </a>";
                }

                return 'N/A';
            })
            ->addColumn('actions', function ($leave) {
                return view('components.datatable-actions', [
                    'id' => $leave->id,
                    'actions' => [
                        [
                            'label' => __('View Details'),
                            'icon' => 'bx bx-show',
                            'onclick' => "viewLeaveDetails({$leave->id})",
                        ],
                    ],
                ])->render();
            })
            ->rawColumns(['user', 'leave_dates', 'status', 'document', 'actions'])
            ->make(true);
    }

    public function actionAjax(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:leave_requests,id',
            'status' => 'required|in:approved,rejected,cancelled',
            'adminNotes' => 'nullable|string',
        ]);

        try {
            $leaveRequest = LeaveRequest::findOrFail($validated['id']);

            if ($validated['status'] == 'cancelled') {
                // Handle cancellation separately since it's not part of approval flow
                $leaveRequest->status = LeaveRequestStatus::CANCELLED;
                $leaveRequest->cancel_reason = $validated['adminNotes'] ?? null;
                $leaveRequest->cancelled_at = now();
                $leaveRequest->save();
            } else {
                // For approval/rejection, use the multilevel approval system
                $latestRequest = $leaveRequest->latestApprovalRequest();

                if ($latestRequest && $latestRequest->isPending()) {
                    // Process through approval system
                    $approvalService = app(\Modules\Approvals\app\Services\ApprovalService::class);

                    $action = $validated['status'] == 'approved' ? 'approved' : 'rejected';
                    $comments = $validated['adminNotes'] ?? null;

                    $result = $approvalService->processAction(
                        $latestRequest,
                        auth()->user(),
                        $action,
                        $comments
                    );

                    // Update the leave request based on approval result
                    $leaveRequest->processApprovalResult();
                } else {
                    // Legacy approval flow if not in approval system
                    $leaveRequest->status = match ($validated['status']) {
                        'approved' => LeaveRequestStatus::APPROVED,
                        'rejected' => LeaveRequestStatus::REJECTED,
                        default => $validated['status']
                    };
                    $leaveRequest->approval_notes = $validated['adminNotes'] ?? null;

                    if ($validated['status'] == 'approved') {
                        $leaveRequest->approved_by_id = auth()->id();
                        $leaveRequest->approved_at = now();
                    } elseif ($validated['status'] == 'rejected') {
                        $leaveRequest->rejected_by_id = auth()->id();
                        $leaveRequest->rejected_at = now();
                    }

                    $leaveRequest->save();
                }
            }

            // Send notification to employee about status change
            try {
                $notificationService = app(HRNotificationService::class);
                $notificationService->sendLeaveRequestApprovalNotification($leaveRequest, $validated['status']);
            } catch (\Exception $e) {
                Log::error('Failed to send leave approval notification: ' . $e->getMessage());
                // Don't fail the request if notification fails
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Leave request '.$validated['status'].' successfully.',
            ]);
        } catch (Exception $e) {
            Log::error('Leave action error: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     * Legacy method name - redirect to indexAjax
     */
    public function getListAjax(Request $request)
    {
        return $this->indexAjax($request);
    }

    /**
     * Show leave request details page
     */
    public function showPage($id)
    {
        // Check if request wants the view page
        if (! request()->ajax() && ! request()->wantsJson()) {
            return view('hrcore::leave.show', compact('id'));
        }

        // For AJAX requests, call the existing show method
        return $this->show($id);
    }

    /**
     * Get leave request details by ID (AJAX)
     */
    public function show($id)
    {
        $leaveRequest = LeaveRequest::with([
            'user.department',
            'user.designation',
            'leaveType',
            'approvedBy',
            'rejectedBy',
            'cancelledBy',
        ])->findOrFail($id);

        // Check permissions using Gate::authorize
        if (! Gate::allows('hrcore.view-leaves') && ! Gate::allows('hrcore.view-own-leaves')) {
            return Error::response(__('Unauthorized access'));
        }

        // If user can only view their own leaves, ensure they own this request
        if (! Gate::allows('hrcore.view-leaves') && Gate::allows('hrcore.view-own-leaves')) {
            if ($leaveRequest->user_id !== auth()->id()) {
                return Error::response(__('Unauthorized access'));
            }
        }

        // Get leave balance for this user and leave type (if LeaveBalance model exists)
        $balance = null;
        if ($leaveRequest->user && $leaveRequest->leaveType && class_exists('Modules\HRCore\app\Models\LeaveBalance')) {
            $balance = \Modules\HRCore\app\Models\LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->first();
        }

        // Get recent leave history
        $leaveHistory = LeaveRequest::where('user_id', $leaveRequest->user_id)
            ->where('id', '!=', $leaveRequest->id)
            ->with(['leaveType'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get status values
        $statusValue = $leaveRequest->status instanceof \BackedEnum ? $leaveRequest->status->value : $leaveRequest->status;

        $response = [
            'id' => $leaveRequest->id,

            // Employee Information
            'user' => [
                'id' => $leaveRequest->user->id,
                'name' => $leaveRequest->user->getFullName(),
                'first_name' => $leaveRequest->user->first_name,
                'last_name' => $leaveRequest->user->last_name,
                'code' => $leaveRequest->user->code,
                'email' => $leaveRequest->user->email,
                'avatar' => $leaveRequest->user->avatar,
                'department' => $leaveRequest->user->department ? $leaveRequest->user->department->name : null,
                'designation' => $leaveRequest->user->designation ? $leaveRequest->user->designation->name : null,
            ],

            // Leave Information
            'leave_type' => [
                'id' => $leaveRequest->leaveType->id,
                'name' => $leaveRequest->leaveType->name,
                'code' => $leaveRequest->leaveType->code,
                'color' => $leaveRequest->leaveType->color ?? '#007bff',
            ],

            // Dates and Duration
            'from_date' => $leaveRequest->from_date,
            'to_date' => $leaveRequest->to_date,
            'from_date_formatted' => $leaveRequest->from_date->format('M d, Y'),
            'to_date_formatted' => $leaveRequest->to_date->format('M d, Y'),
            'date_range_display' => $leaveRequest->date_range_display,
            'is_half_day' => $leaveRequest->is_half_day,
            'half_day_type' => $leaveRequest->half_day_type,
            'half_day_display' => $leaveRequest->half_day_display,
            'total_days' => $leaveRequest->total_days,

            // Request Details
            'user_notes' => $leaveRequest->user_notes,
            'emergency_contact' => $leaveRequest->emergency_contact,
            'emergency_phone' => $leaveRequest->emergency_phone,
            'contact_during_leave' => $leaveRequest->contact_during_leave,
            'is_abroad' => $leaveRequest->is_abroad,
            'abroad_location' => $leaveRequest->abroad_location,

            // Document
            'document' => $leaveRequest->document,
            'document_url' => $leaveRequest->getLeaveDocumentUrl(),
            'has_document' => ! empty($leaveRequest->document) || ! empty($leaveRequest->getLeaveDocumentFile()),

            // Status Information
            'status' => $statusValue,
            'status_display' => ucfirst($statusValue),
            'status_color' => [
                'pending' => 'warning',
                'approved' => 'success',
                'rejected' => 'danger',
                'cancelled' => 'secondary',
            ][$statusValue] ?? 'secondary',

            // Approval Information
            'approved_by_id' => $leaveRequest->approved_by_id,
            'approved_by' => $leaveRequest->approvedBy ? [
                'name' => $leaveRequest->approvedBy->getFullName(),
                'code' => $leaveRequest->approvedBy->code,
            ] : null,
            'approved_at' => $leaveRequest->approved_at?->format('M d, Y H:i'),
            'approval_notes' => $leaveRequest->approval_notes,

            // Rejection Information
            'rejected_by_id' => $leaveRequest->rejected_by_id,
            'rejected_by' => $leaveRequest->rejectedBy ? [
                'name' => $leaveRequest->rejectedBy->getFullName(),
                'code' => $leaveRequest->rejectedBy->code,
            ] : null,
            'rejected_at' => $leaveRequest->rejected_at?->format('M d, Y H:i'),

            // Cancellation Information
            'cancelled_by_id' => $leaveRequest->cancelled_by_id,
            'cancelled_by' => $leaveRequest->cancelledBy ? [
                'name' => $leaveRequest->cancelledBy->getFullName(),
                'code' => $leaveRequest->cancelledBy->code,
            ] : null,
            'cancelled_at' => $leaveRequest->cancelled_at?->format('M d, Y H:i'),
            'cancel_reason' => $leaveRequest->cancel_reason,
            'cancellation_reason' => $leaveRequest->cancel_reason, // Alias for consistency

            // Timestamps
            'created_at' => $leaveRequest->created_at->format('M d, Y H:i'),
            'updated_at' => $leaveRequest->updated_at->format('M d, Y H:i'),
            'created_at_human' => $leaveRequest->created_at->diffForHumans(),

            // Leave Balance
            'balance' => $balance ? [
                'entitled_leaves' => $balance->entitled_leaves,
                'used_leaves' => $balance->used_leaves,
                'available_leaves' => $balance->available_leaves,
                'pending_leaves' => $balance->pending_leaves ?? 0,
            ] : null,

            // Recent Leave History
            'leave_history' => $leaveHistory->map(function ($history) {
                $historyStatusValue = $history->status instanceof \BackedEnum ? $history->status->value : $history->status;

                return [
                    'id' => $history->id,
                    'leave_type' => $history->leaveType->name,
                    'from_date' => $history->from_date->format('M d'),
                    'to_date' => $history->to_date->format('M d, Y'),
                    'total_days' => $history->total_days,
                    'status' => $historyStatusValue,
                    'status_color' => [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'cancelled' => 'secondary',
                    ][$historyStatusValue] ?? 'secondary',
                ];
            }),

            // Permissions for current user
            'can_approve' => auth()->user()->can('hrcore.approve-leave') ||
                            ($leaveRequest->user->reporting_to_id === auth()->id()),
            'can_reject' => auth()->user()->can('hrcore.approve-leave') ||
                           ($leaveRequest->user->reporting_to_id === auth()->id()),
            'can_cancel' => ($leaveRequest->user_id === auth()->id() || auth()->user()->can('hrcore.cancel-leave')) &&
                           in_array($statusValue, ['pending', 'approved']),
            'can_edit' => ($leaveRequest->user_id === auth()->id() || auth()->user()->can('hrcore.edit-leave')) &&
                         $statusValue === 'pending',
            'can_delete' => auth()->user()->can('hrcore.delete-leave'),
        ];

        return Success::response($response);
    }

    /**
     * Legacy method - redirect to show
     */
    public function getByIdAjax($id)
    {
        return $this->show($id);
    }

    /**
     * Approve a leave request
     */
    public function approve(Request $request, $id)
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            // Check permissions using Gate::authorize
            Gate::authorize('hrcore.approve-leave');

            // Validate current status
            if ($leaveRequest->status->value !== 'pending') {
                return Error::response(__('Only pending leave requests can be approved'));
            }

            DB::beginTransaction();

            // Update leave request
            $leaveRequest->status = LeaveRequestStatus::APPROVED;
            $leaveRequest->approved_by_id = auth()->id();
            $leaveRequest->approved_at = now();
            $leaveRequest->approval_notes = $request->input('notes');
            $leaveRequest->save();

            // Send notification (if notification system exists)
            if (class_exists('App\Notifications\Leave\LeaveRequestApproval')) {
                $leaveRequest->user->notify(new LeaveRequestApproval($leaveRequest, 'approved'));
            }

            DB::commit();

            return Success::response([
                'message' => __('Leave request approved successfully'),
                'leave_request' => $leaveRequest,
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Leave approval error: '.$e->getMessage());

            return Error::response(__('Failed to approve leave request'));
        }
    }

    /**
     * Reject a leave request
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            // Check permissions using Gate::authorize
            Gate::authorize('hrcore.reject-leave');

            // Validate current status
            if ($leaveRequest->status->value !== 'pending') {
                return Error::response(__('Only pending leave requests can be rejected'));
            }

            DB::beginTransaction();

            // Update leave request
            $leaveRequest->status = LeaveRequestStatus::REJECTED;
            $leaveRequest->rejected_by_id = auth()->id();
            $leaveRequest->rejected_at = now();
            $leaveRequest->approval_notes = $request->input('reason');
            $leaveRequest->save();

            // Send notification (if notification system exists)
            if (class_exists('App\Notifications\Leave\LeaveRequestApproval')) {
                $leaveRequest->user->notify(new LeaveRequestApproval($leaveRequest, 'rejected'));
            }

            DB::commit();

            return Success::response([
                'message' => __('Leave request rejected successfully'),
                'leave_request' => $leaveRequest,
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Leave rejection error: '.$e->getMessage());

            return Error::response(__('Failed to reject leave request'));
        }
    }

    /**
     * Cancel a leave request
     */
    public function cancel(Request $request, $id)
    {
        try {
            $leaveRequest = LeaveRequest::findOrFail($id);

            // Check permissions using Gate::authorize
            if ($leaveRequest->user_id !== auth()->id()) {
                Gate::authorize('hrcore.cancel-leave');
            }

            if (! in_array($leaveRequest->status->value, ['pending', 'approved'])) {
                return Error::response(__('Only pending or approved leave requests can be cancelled'));
            }

            // Check if leave is in the future
            if ($leaveRequest->from_date->isPast()) {
                return Error::response(__('Cannot cancel leave that has already started or passed'));
            }

            DB::beginTransaction();

            // Cancel the leave request
            $leaveRequest->cancel($request->input('reason', ''), auth()->user()->can('hrcore.cancel-leave'));

            // Send notification (if notification system exists)
            if (class_exists('App\Notifications\Leave\LeaveRequestApproval')) {
                if ($leaveRequest->user_id !== auth()->id()) {
                    $leaveRequest->user->notify(new LeaveRequestApproval($leaveRequest, 'cancelled'));
                }
            }

            DB::commit();

            return Success::response([
                'message' => __('Leave request cancelled successfully'),
                'leave_request' => $leaveRequest,
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Leave cancellation error: '.$e->getMessage());

            return Error::response(__('Failed to cancel leave request'));
        }
    }
}
