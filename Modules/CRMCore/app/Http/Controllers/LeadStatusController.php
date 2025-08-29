<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Success;
use App\Http\Responses\Error;
use Modules\CRMCore\app\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Exception;

class LeadStatusController extends Controller
{
  public function index()
  {
    // For drag-and-drop list, you might fetch all and pass to view.
    // For DataTables, it's handled by getDataAjax.
    $leadStatuses = LeadStatus::orderBy('position')->get();
    return view('crmcore::lead_statuses.index', compact('leadStatuses'));
  }

  public function getDataAjax(Request $request) // Optional: if using DataTables
  {
    $query = LeadStatus::query()->orderBy('position');
    return DataTables::of($query)
      ->addColumn('color_display', function ($status) {
        return '<span class="badge" style="background-color:' . ($status->color ?: '#6c757d') . '; color: #fff;">' . ($status->color ?: 'Default') . '</span>';
      })
      ->addColumn('is_default_display', function ($status) {
        return $status->is_default ? '<span class="badge bg-label-success">Yes</span>' : '<span class="badge bg-label-secondary">No</span>';
      })
      ->addColumn('is_final_display', function ($status) {
        return $status->is_final ? '<span class="badge bg-label-info">Yes</span>' : '<span class="badge bg-label-secondary">No</span>';
      })
      ->addColumn('actions', function ($status) {
        $editUrl = route('crmcore::leadStatuses.getLeadStatusAjax', $status->id);
        $deleteUrl = route('crmcore::leadStatuses.destroy', $status->id);
        $editButton = '<button class="btn btn-sm btn-icon me-1 edit-lead-status" data-url="' . $editUrl . '" title="Edit"><i class="bx bx-pencil"></i></button>';
        $deleteButton = '<button class="btn btn-sm btn-icon text-danger delete-lead-status" data-id="' . $status->id . '" data-url="' . $deleteUrl . '" title="Delete"><i class="bx bx-trash"></i></button>';
        return $editButton . $deleteButton;
      })
      ->rawColumns(['color_display', 'is_default_display', 'is_final_display', 'actions'])
      ->make(true);
  }

  public function getLeadStatusAjax(LeadStatus $lead_status)
  {
    return response()->json($lead_status);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255|unique:lead_statuses,name',
      'color' => 'nullable|string|max:20', // Basic validation, could be regex for hex
      'is_default' => 'boolean',
      'is_final' => 'boolean',
    ]);

    if ($validator->fails()) {
      return Error::response([
        'message' => __('Validation failed'),
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      DB::beginTransaction();
      $data = $validator->validated();
      $data['is_default'] = $request->boolean('is_default');
      $data['is_final'] = $request->boolean('is_final');

      if ($data['is_default']) { // Ensure only one default
        LeadStatus::where('is_default', true)->update(['is_default' => false]);
      }
      // Set position for new status
      $data['position'] = (LeadStatus::max('position') ?? 0) + 1;

      LeadStatus::create($data);
      DB::commit();
      return Success::response(['message' => __('Lead Status created successfully.')]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error("LeadStatus Store: " . $e->getMessage());
      return Error::response(__('Failed to create status.'), 500);
    }
  }

  public function update(Request $request, LeadStatus $lead_status)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255|unique:lead_statuses,name,' . $lead_status->id,
      'color' => 'nullable|string|max:20',
      'is_default' => 'boolean',
      'is_final' => 'boolean',
    ]);

    if ($validator->fails()) {
      return Error::response([
        'message' => __('Validation failed'),
        'errors' => $validator->errors()
      ], 422);
    }

    try {
      DB::beginTransaction();
      $data = $validator->validated();
      $data['is_default'] = $request->boolean('is_default');
      $data['is_final'] = $request->boolean('is_final');

      if ($data['is_default']) { // Ensure only one default
        LeadStatus::where('id', '!=', $lead_status->id)->where('is_default', true)->update(['is_default' => false]);
      }

      $lead_status->update($data);
      DB::commit();
      return Success::response(['message' => __('Lead Status updated successfully.')]);
    } catch (Exception $e) {
      DB::rollBack();
      Log::error("LeadStatus Update: " . $e->getMessage());
      return Error::response(__('Failed to update status.'), 500);
    }
  }

  public function destroy(LeadStatus $lead_status)
  {
    try {
      if ($lead_status->leads()->exists()) {
        return Error::response(__('Cannot delete status as it is associated with leads.'), 400);
      }
      if ($lead_status->is_default) {
        return Error::response(__('Cannot delete the default status. Assign another status as default first.'), 400);
      }
      $lead_status->delete();
      return Success::response(['message' => __('Lead Status deleted successfully.')]);
    } catch (Exception $e) {
      Log::error("LeadStatus Delete: " . $e->getMessage());
      return Error::response(__('Failed to delete status.'), 500);
    }
  }

  public function updateOrder(Request $request)
  {
    $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:lead_statuses,id']);
    try {
      foreach ($request->input('order') as $index => $id) {
        LeadStatus::where('id', $id)->update(['position' => $index + 1]);
      }
      return Success::response(['message' => __('Status order updated successfully.')]);
    } catch (Exception $e) {
      Log::error("LeadStatus Order Update: " . $e->getMessage());
      return Error::response(__('Failed to update order.'), 500);
    }
  }
}
