<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\Task;
use Modules\CRMCore\app\Models\TaskPriority;
use Modules\CRMCore\app\Models\TaskStatus;
use Modules\CRMCore\app\Notifications\TaskAssignedNotification;
use Yajra\DataTables\Facades\DataTables;

class TaskController extends Controller
{
    public function __construct()
    {
        /* $this->middleware('permission:view_tasks')->only(['index', 'getDataTableAjax', 'getTaskAjax']);
         $this->middleware('permission:create_tasks')->only(['store']);
         $this->middleware('permission:edit_tasks')->only(['update']);
         $this->middleware('permission:delete_tasks')->only(['destroy']);
         $this->middleware('permission:complete_tasks')->only(['update']);*/
    }

    /**
     * Display a listing of the tasks.
     */
    public function index()
    {
        $taskStatuses = TaskStatus::orderBy('position')->get();
        $taskPriorities = TaskPriority::orderBy('level')->pluck('name', 'id');

        $relatedToUrls = [
            'contacts' => route('contacts.selectSearch'),
            'companies' => route('companies.selectSearch'),
            'leads' => route('leads.selectSearch'),
            'deals' => route('deals.selectSearch'),
        ];

        return view('crmcore::tasks.index', compact('taskStatuses', 'taskPriorities', 'relatedToUrls'));
    }

    /**
     * Process DataTables ajax request for tasks.
     */
    public function getDataTableAjax(Request $request)
    {
        $query = Task::with([
            'status:id,name,color',
            'priority:id,name,color',
            'assignedToUser:id,first_name,last_name',
            'taskable', // Eager load the polymorphic relation
        ])->select('crm_tasks.*');

        // Basic Filtering Examples (can be expanded)
        if ($request->filled('status_id')) {
            $query->where('task_status_id', $request->input('status_id'));
        }
        if ($request->filled('priority_id')) {
            $query->where('task_priority_id', $request->input('priority_id'));
        }
        if ($request->filled('assigned_to_user_id')) {
            $query->where('assigned_to_user_id', $request->input('assigned_to_user_id'));
        }
        if ($request->filled('due_date_range')) {
            // Implement date range filtering for due_date
        }

        return DataTables::of($query)
            ->editColumn('title', function ($task) {
                $class = $task->completed_at ? 'text-decoration-line-through text-muted' : '';

                return '<span class="'.$class.'">'.e($task->title).'</span>';
            })
            ->addColumn('status_formatted', function ($task) {
                $color = $task->status?->color ?? '#6c757d';

                return '<span class="badge" style="background-color:'.$color.'; color: #fff;">'.e($task->status?->name ?? 'N/A').'</span>';
            })
            ->addColumn('priority_formatted', function ($task) {
                $color = $task->priority?->color ?? '#6c757d';

                return '<span class="badge" style="background-color:'.$color.'; color: #fff;">'.e($task->priority?->name ?? 'N/A').'</span>';
            })
            ->addColumn('assigned_to', function ($task) {
                return $task->assignedToUser ? view('components.datatable-user', ['user' => $task->assignedToUser])->render() : 'N/A';
            })
            ->addColumn('related_to', function ($task) {
                if ($task->taskable) {
                    $type = class_basename($task->taskable_type);
                    $name = '';
                    switch ($type) {
                        case 'Contact':
                            $name = $task->taskable->full_name;
                            break;
                        case 'Company':
                            $name = $task->taskable->name;
                            break;
                        case 'Lead':
                            $name = $task->taskable->title;
                            break;
                        case 'Deal':
                            $name = $task->taskable->title;
                            break;
                        default:
                            $name = 'Record ID: '.$task->taskable_id;
                    }

                    return e($name).' ('.e($type).')';
                }

                return 'N/A';
            })
            ->editColumn('due_date', fn ($task) => $task->due_date ? $task->due_date->format('d M Y, H:i') : '-')
            ->addColumn('actions', function ($task) {
                $actions = [];

                if (! $task->completed_at) {
                    $completedStatusId = TaskStatus::where('is_completed_status', true)->first()?->id;
                    $actions[] = [
                        'label' => __('Mark Complete'),
                        'icon' => 'bx bx-check-square',
                        'class' => 'text-success mark-task-complete',
                        'attributes' => 'data-url="'.route('tasks.update', $task->id).'" data-status-id="'.$completedStatusId.'"',
                    ];
                }

                $actions[] = [
                    'label' => __('Edit'),
                    'icon' => 'bx bx-edit',
                    'onclick' => "editTask({$task->id})",
                ];

                $actions[] = [
                    'label' => __('Delete'),
                    'icon' => 'bx bx-trash',
                    'class' => 'text-danger',
                    'onclick' => "deleteTask({$task->id})",
                ];

                return view('components.datatable-actions', [
                    'id' => $task->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['title', 'status_formatted', 'priority_formatted', 'assigned_to', 'actions'])
            ->make(true);
    }

    /**
     * Get tasks for kanban board view.
     */
    public function getKanbanAjax(Request $request)
    {
        $tasks = Task::with([
            'status:id,name,color',
            'priority:id,name,color',
            'assignedToUser:id,first_name,last_name',
            'taskable',
        ])
            ->whereNull('completed_at')
            ->orderBy('due_date', 'asc')
            ->orderBy('id', 'desc')
            ->get();

        $tasksByStatus = [];
        foreach ($tasks as $task) {
            $statusId = $task->task_status_id;
            if (! isset($tasksByStatus[$statusId])) {
                $tasksByStatus[$statusId] = [];
            }

            $taskData = [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date ? $task->due_date->format('M d, Y') : null,
                'is_overdue' => $task->due_date && $task->due_date->isPast(),
                'priority' => [
                    'name' => $task->priority?->name ?? 'None',
                    'color' => $task->priority?->color ?? '#6c757d',
                ],
                'assigned_to' => $task->assignedToUser ? [
                    'id' => $task->assignedToUser->id,
                    'name' => $task->assignedToUser->getFullName(),
                    'avatar' => $task->assignedToUser->getProfilePicture(),
                ] : null,
                'related_to' => null,
            ];

            if ($task->taskable) {
                $type = class_basename($task->taskable_type);
                $name = '';
                switch ($type) {
                    case 'Contact':
                        $name = $task->taskable->full_name;
                        break;
                    case 'Company':
                        $name = $task->taskable->name;
                        break;
                    case 'Lead':
                        $name = $task->taskable->title;
                        break;
                    case 'Deal':
                        $name = $task->taskable->title;
                        break;
                }
                $taskData['related_to'] = [
                    'type' => $type,
                    'name' => $name,
                ];
            }

            $tasksByStatus[$statusId][] = $taskData;
        }

        return response()->json(['tasks' => $tasksByStatus]);
    }

    /**
     * Update task kanban status.
     */
    public function updateKanbanStatus(Request $request, Task $task)
    {
        $request->validate([
            'task_status_id' => 'required|exists:task_statuses,id',
        ]);

        try {
            DB::beginTransaction();

            $newStatusId = $request->input('task_status_id');
            $status = TaskStatus::find($newStatusId);

            $task->task_status_id = $newStatusId;

            if ($status->is_completed_status && ! $task->completed_at) {
                $task->completed_at = now();
            } elseif (! $status->is_completed_status && $task->completed_at) {
                $task->completed_at = null;
            }

            $task->save();

            DB::commit();

            return Success::response([
                'message' => __('Task status updated successfully'),
                'data' => $task,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Task Kanban Update Error for ID {$task->id}: ".$e->getMessage());

            return Error::response(__('Failed to update task status'));
        }
    }

    /**
     * Fetch a single task's data for the offcanvas edit form.
     */
    public function getTaskAjax(Task $task)
    {
        $task->load(['assignedToUser:id,first_name,last_name', 'taskable', 'status', 'priority']);

        return response()->json($task);
    }

    /**
     * Store a newly created task from the offcanvas form.
     */
    public function store(Request $request)
    {
        $validator = $this->validateTask($request);
        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();

            if (empty($data['task_status_id'])) {
                $data['task_status_id'] = TaskStatus::where('is_default', true)->firstOrFail()->id;
            }
            if (empty($data['task_priority_id'])) {
                $data['task_priority_id'] = TaskPriority::where('is_default', true)->firstOrFail()->id;
            }

            // Handle taskable (related to)
            if ($request->filled('taskable_type_selector') && $request->filled('taskable_id_selector')) {
                $taskableTypeInput = $request->input('taskable_type_selector'); // e.g., 'Contact', 'Company'
                $taskableId = $request->input('taskable_id_selector');

                // Map selector value to actual model class name
                $modelMap = [
                    'Contact' => Contact::class,
                    'Company' => Company::class,
                    'Lead' => Lead::class,
                    'Deal' => Deal::class,
                ];
                if (isset($modelMap[$taskableTypeInput])) {
                    $data['taskable_type'] = $modelMap[$taskableTypeInput];
                    $data['taskable_id'] = $taskableId;
                }
            }

            $task = Task::create($data);
            if ($task->assigned_to_user_id) {
                $user = User::find($task->assigned_to_user_id);
                if ($user) {
                    $user->notify(new TaskAssignedNotification('New Task Assigned', $task->title));
                }
            }
            DB::commit();

            return Success::response([
                'message' => __('Task created successfully'),
                'data' => $task,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Task Store Error: '.$e->getMessage());

            return Error::response(__('Failed to create task'));
        }
    }

    /**
     * Update the specified task from the offcanvas form or for quick actions like marking complete.
     */
    public function update(Request $request, Task $task)
    {
        // Check if it's a quick status update (e.g., mark complete)
        if ($request->has('task_status_id_quick')) {
            $newStatusId = $request->input('task_status_id_quick');
            $status = TaskStatus::find($newStatusId);
            if ($status) {
                $task->task_status_id = $newStatusId;
                if ($status->is_completed_status && ! $task->completed_at) {
                    $task->completed_at = now();
                } elseif (! $status->is_completed_status && $task->completed_at) {
                    $task->completed_at = null; // Re-opening task
                }
                $task->save();

                return Success::response([
                    'message' => __('Task status updated successfully'),
                    'data' => $task,
                ]);
            }

            return Error::response(__('Invalid status provided'));
        }

        // Full update from form
        $validator = $this->validateTask($request, $task->id);
        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();

            // Handle completed_at based on status
            if (isset($data['task_status_id'])) {
                $status = TaskStatus::find($data['task_status_id']);
                if ($status && $status->is_completed_status && ! $task->completed_at) {
                    $data['completed_at'] = now();
                } elseif ($status && ! $status->is_completed_status && $task->completed_at) {
                    $data['completed_at'] = null;
                }
            }

            // Handle taskable (related to)
            if ($request->filled('taskable_type_selector') && $request->filled('taskable_id_selector')) {
                $taskableTypeInput = $request->input('taskable_type_selector');
                $taskableId = $request->input('taskable_id_selector');
                $modelMap = ['Contact' => Contact::class, 'Company' => Company::class, 'Lead' => Lead::class, 'Deal' => Deal::class];
                if (isset($modelMap[$taskableTypeInput])) {
                    $data['taskable_type'] = $modelMap[$taskableTypeInput];
                    $data['taskable_id'] = $taskableId;
                } else { // If type is invalid, clear taskable
                    $data['taskable_type'] = null;
                    $data['taskable_id'] = null;
                }
            } elseif ($request->has('taskable_type_selector') && $request->input('taskable_type_selector') === '') { // If "None" is selected
                $data['taskable_type'] = null;
                $data['taskable_id'] = null;
            }

            $task->update($data);
            DB::commit();

            return Success::response([
                'message' => __('Task updated successfully'),
                'data' => $task,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Task Update Error for ID {$task->id}: ".$e->getMessage());

            return Error::response(__('Failed to update task'));
        }
    }

    /**
     * Remove the specified task from storage.
     */
    public function destroy(Task $task)
    {
        try {
            $task->delete();

            return Success::response([
                'message' => __('Task deleted successfully'),
            ]);
        } catch (Exception $e) {
            Log::error("Task Delete Error for ID {$task->id}: ".$e->getMessage());

            return Error::response(__('Failed to delete task'));
        }
    }

    /**
     * Reusable validation helper for tasks.
     */
    private function validateTask(Request $request, $taskId = null)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'reminder_at' => 'nullable|date|after_or_equal:now', // Basic reminder validation
            'task_status_id' => 'required|exists:task_statuses,id',
            'task_priority_id' => 'nullable|exists:task_priorities,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            // For the simple approach of selecting type and then ID from separate dropdowns
            'taskable_type_selector' => 'nullable|string|in:Contact,Company,Lead,Deal', // This is a helper input, not a direct model field
            'taskable_id_selector' => 'nullable|integer', // This is a helper input
        ];

        // Custom validation logic for taskable_id_selector based on taskable_type_selector
        return Validator::make($request->all(), $rules)->after(function ($validator) use ($request) {
            $type = $request->input('taskable_type_selector');
            $id = $request->input('taskable_id_selector');

            if ($type && $id) {
                $modelClass = null;
                switch ($type) {
                    case 'Contact':
                        $modelClass = Contact::class;
                        break;
                    case 'Company':
                        $modelClass = Company::class;
                        break;
                    case 'Lead':
                        $modelClass = Lead::class;
                        break;
                    case 'Deal':
                        $modelClass = Deal::class;
                        break;
                }
                if ($modelClass && ! DB::table((new $modelClass)->getTable())->where('id', $id)->exists()) {
                    $validator->errors()->add('taskable_id_selector', "The selected {$type} does not exist.");
                }
            } elseif ($type && ! $id) {
                $validator->errors()->add('taskable_id_selector', "Please select a {$type}.");
            } elseif (! $type && $id) {
                $validator->errors()->add('taskable_type_selector', 'Please select a type for the related item.');
            }
        });
    }
}
