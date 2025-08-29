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
use Modules\CRMCore\app\Models\TaskStatus;

class TaskStatusController extends Controller
{
    public function index()
    {
        $taskStatuses = TaskStatus::orderBy('position')->get();

        return view('crmcore::task_statuses.index', compact('taskStatuses'));
    }

    public function getTaskStatusAjax(TaskStatus $task_status)
    {
        return response()->json($task_status);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_statuses,name',
            'color' => 'nullable|string|max:20',
            'is_default' => 'boolean',
            'is_completed_status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['is_completed_status'] = $request->boolean('is_completed_status');

            if ($data['is_default']) {
                TaskStatus::where('is_default', true)->update(['is_default' => false]);
            }

            $data['position'] = (TaskStatus::max('position') ?? 0) + 1;

            TaskStatus::create($data);
            DB::commit();

            return Success::response(['message' => __('Task Status created successfully.')]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TaskStatus Store: '.$e->getMessage());

            return Error::response(__('Failed to create status.'), 500);
        }
    }

    public function update(Request $request, TaskStatus $task_status)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:task_statuses,name,'.$task_status->id,
            'color' => 'nullable|string|max:20',
            'is_default' => 'boolean',
            'is_completed_status' => 'boolean',
        ]);

        if ($validator->fails()) {
            return Error::response([
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_default'] = $request->boolean('is_default');
            $data['is_completed_status'] = $request->boolean('is_completed_status');

            if ($data['is_default']) {
                TaskStatus::where('id', '!=', $task_status->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $task_status->update($data);
            DB::commit();

            return Success::response(['message' => __('Task Status updated successfully.')]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('TaskStatus Update: '.$e->getMessage());

            return Error::response(__('Failed to update status.'), 500);
        }
    }

    public function destroy(TaskStatus $task_status)
    {
        try {
            if ($task_status->tasks()->exists()) {
                return Error::response(__('Cannot delete status as it is currently assigned to tasks.'), 400);
            }
            if ($task_status->is_default && TaskStatus::count() > 1) {
                return Error::response(__('Cannot delete the default status. Assign another status as default first.'), 400);
            }

            $task_status->delete();

            return Success::response(['message' => __('Task Status deleted successfully.')]);
        } catch (Exception $e) {
            Log::error('TaskStatus Delete: '.$e->getMessage());

            return Error::response(__('Failed to delete status.'), 500);
        }
    }

    public function updateOrder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:task_statuses,id']);
        try {
            foreach ($request->input('order') as $index => $id) {
                TaskStatus::where('id', $id)->update(['position' => $index + 1]);
            }

            return Success::response(['message' => __('Status order updated successfully.')]);
        } catch (Exception $e) {
            Log::error('TaskStatus Order Update: '.$e->getMessage());

            return Error::response(__('Failed to update order.'), 500);
        }
    }
}
