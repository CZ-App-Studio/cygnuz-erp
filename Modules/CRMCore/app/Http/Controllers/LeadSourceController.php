<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Error;
use App\Http\Responses\Success;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\LeadSource;
use Yajra\DataTables\Facades\DataTables;

class LeadSourceController extends Controller
{
    public function index()
    {
        return view('crmcore::lead_sources.index');
    }

    public function getDataAjax(Request $request)
    {
        $query = LeadSource::query();

        return DataTables::of($query)
            ->editColumn('is_active', function ($source) {
                $badgeClass = $source->is_active ? 'bg-label-success' : 'bg-label-secondary';
                $badgeText = $source->is_active ? __('Active') : __('Inactive');

                return '<span class="badge '.$badgeClass.'">'.$badgeText.'</span>';
            })
            ->addColumn('actions', function ($source) {
                $actions = [
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'class' => 'edit-lead-source',
                        'data' => [
                            'url' => route('settings.leadSources.getLeadSourceAjax', $source->id),
                        ],
                    ],
                    [
                        'label' => $source->is_active ? __('Disable') : __('Enable'),
                        'icon' => $source->is_active ? 'bx bx-block' : 'bx bx-check-circle',
                        'class' => 'toggle-status',
                        'data' => [
                            'id' => $source->id,
                            'url' => route('settings.leadSources.toggleStatus', $source->id),
                        ],
                    ],
                    [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'class' => 'delete-lead-source text-danger',
                        'data' => [
                            'id' => $source->id,
                            'url' => route('settings.leadSources.destroy', $source->id),
                        ],
                    ],
                ];

                return view('components.datatable-actions', [
                    'id' => $source->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['is_active', 'actions'])
            ->make(true);
    }

    public function getLeadSourceAjax(LeadSource $lead_source)
    {
        return response()->json($lead_source);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), ['name' => 'required|string|max:255|unique:lead_sources,name']);
        if ($validator->fails()) {
            return Error::response(['message' => __('Validation failed'), 'errors' => $validator->errors()], 422);
        }
        try {
            LeadSource::create(['name' => $request->input('name'), 'is_active' => $request->boolean('is_active', true)]);

            return Success::response(['message' => __('Lead Source created successfully.')]);
        } catch (Exception $e) {
            Log::error('LeadSource Store: '.$e->getMessage());

            return Error::response(__('Failed to create source.'), 500);
        }
    }

    public function update(Request $request, LeadSource $lead_source)
    {
        $validator = Validator::make($request->all(), ['name' => 'required|string|max:255|unique:lead_sources,name,'.$lead_source->id]);
        if ($validator->fails()) {
            return Error::response(['message' => __('Validation failed'), 'errors' => $validator->errors()], 422);
        }
        try {
            $lead_source->update(['name' => $request->input('name'), 'is_active' => $request->boolean('is_active')]);

            return Success::response(['message' => __('Lead Source updated successfully.')]);
        } catch (Exception $e) {
            Log::error('LeadSource Update: '.$e->getMessage());

            return Error::response(__('Failed to update source.'), 500);
        }
    }

    public function destroy(LeadSource $lead_source)
    {
        try {
            if ($lead_source->leads()->exists()) {
                return Error::response(__('Cannot delete source as it is associated with leads.'), 400);
            }
            $lead_source->delete();

            return Success::response(['message' => __('Lead Source deleted successfully.')]);
        } catch (Exception $e) {
            Log::error('LeadSource Delete: '.$e->getMessage());

            return Error::response(__('Failed to delete source.'), 500);
        }
    }

    public function toggleStatus(Request $request, LeadSource $lead_source)
    {
        try {
            $lead_source->is_active = ! $lead_source->is_active;
            $lead_source->save();

            return Success::response(['message' => __('Status updated successfully'), 'is_active' => $lead_source->is_active]);
        } catch (Exception $e) {
            Log::error('LeadSource Toggle Status Error: '.$e->getMessage());

            return Error::response(__('Failed to update status.'), 500);
        }
    }
}
