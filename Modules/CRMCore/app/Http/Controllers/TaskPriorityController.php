<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Error;
use App\Http\Responses\Success;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\TaskPriority;
use Yajra\DataTables\Facades\DataTables;

class TaskPriorityController extends Controller
{
    public function index()
    {
        $taskPriorities = TaskPriority::orderBy('level', 'asc')->get();

        return view('crmcore::task_priorities.index', compact('taskPriorities'));
    }

    public function getDataAjax(Request $request)
    {
        $query = TaskPriority::query()->orderBy('level', 'asc'); // Order by level

        return DataTables::of($query)
            ->addColumn('color_display', function ($priority) {
                return '<span class="badge" style="background-color:'.($priority->color ?: '#6c757d').'; color: #fff;">'.($priority->color ?: 'Default').'</span>';
            })
            ->addColumn('is_default_display', function ($priority) {
                return $priority->is_default ? '<span class="badge bg-label-success">Yes</span>' : '<span class="badge bg-label-secondary">No</span>';
            })
            ->addColumn('actions', function ($priority) {
                $actions = [
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'class' => 'edit-task-priority',
                        'data' => [
                            'url' => route('settings.taskPriorities.getPriorityAjax', $priority->id),
                        ],
                    ],
                    [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'class' => 'delete-task-priority text-danger',
                        'data' => [
                            'id' => $priority->id,
                            'url' => route('settings.taskPriorities.destroy', $priority->id),
                        ],
                    ],
                ];

                return view('components.datatable-actions', [
                    'id' => $priority->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['color_display', 'is_default_display', 'actions'])
            ->make(true);
    }

    public function getPriorityAjax(TaskPriority $task_priority)
    {
        return Success::response($task_priority);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_priorities,name',
            'color' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_default'] = $request->boolean('is_default');

            // Auto-assign level as the next highest value
            $maxLevel = TaskPriority::max('level') ?? 0;
            $data['level'] = $maxLevel + 1;

            if ($data['is_default']) {
                TaskPriority::where('is_default', true)->update(['is_default' => false]);
            }

            TaskPriority::create($data);
            DB::commit();

            return Success::response([
                'message' => __('Task Priority created successfully.'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TaskPriority Store: '.$e->getMessage());

            return Error::response(__('Failed to create priority.'), 500);
        }
    }

    public function update(Request $request, TaskPriority $task_priority)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_priorities,name,'.$task_priority->id,
            'color' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_default'] = $request->boolean('is_default');

            // Keep the existing level, don't update it here
            unset($data['level']);

            if ($data['is_default']) {
                TaskPriority::where('id', '!=', $task_priority->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $task_priority->update($data);
            DB::commit();

            return Success::response([
                'message' => __('Task Priority updated successfully.'),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TaskPriority Update: '.$e->getMessage());

            return Error::response(__('Failed to update priority.'), 500);
        }
    }

    public function destroy(TaskPriority $task_priority)
    {
        try {
            if ($task_priority->tasks()->exists()) {
                return Error::response(__('Cannot delete priority as it is currently assigned to tasks.'), 400);
            }
            if ($task_priority->is_default && TaskPriority::count() > 1) {
                return Error::response(__('Cannot delete the default priority. Assign another priority as default first.'), 400);
            }

            $task_priority->delete();

            return Success::response([
                'message' => __('Task Priority deleted successfully.'),
            ]);
        } catch (Exception $e) {
            Log::error('TaskPriority Delete: '.$e->getMessage());

            return Error::response(__('Failed to delete priority.'), 500);
        }
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:task_priorities,id',
        ]);

        try {
            foreach ($request->input('order') as $index => $id) {
                TaskPriority::where('id', $id)->update(['level' => $index + 1]);
            }

            return Success::response([
                'message' => __('Priority order updated successfully.'),
            ]);
        } catch (Exception $e) {
            Log::error('TaskPriority Order Update: '.$e->getMessage());

            return Error::response(__('Failed to update order.'), 500);
        }
    }
}
