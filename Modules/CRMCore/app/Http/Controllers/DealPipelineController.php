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
use Modules\CRMCore\app\Models\DealPipeline;
use Yajra\DataTables\Facades\DataTables;

class DealPipelineController extends Controller
{
    public function index()
    {
        return view('crmcore::deal_pipelines.index');
    }

    public function datatable(Request $request)
    {
        $pipelines = DealPipeline::orderBy('position')->select(['id', 'name', 'description', 'is_default', 'is_active', 'position']);

        return DataTables::of($pipelines)
            ->addColumn('order', function ($pipeline) {
                return '<i class="bx bx-grid-vertical text-muted" style="cursor: move;" title="'.__('Drag to reorder').'"></i>';
            })
            ->editColumn('name', function ($pipeline) {
                $html = '<strong>'.e($pipeline->name).'</strong>';
                if ($pipeline->is_default) {
                    $html .= ' <span class="badge bg-label-primary ms-2">'.__('Default').'</span>';
                }

                return $html;
            })
            ->editColumn('description', function ($pipeline) {
                return $pipeline->description ? '<small class="text-muted">'.e($pipeline->description).'</small>' : '-';
            })
            ->addColumn('status', function ($pipeline) {
                // Boolean cast in model will handle null/1/0 properly
                $badgeClass = $pipeline->is_active ? 'bg-label-success' : 'bg-label-secondary';
                $badgeText = $pipeline->is_active ? __('Active') : __('Inactive');

                return '<span class="badge '.$badgeClass.'">'.$badgeText.'</span>';
            })
            ->addColumn('actions', function ($pipeline) {
                $actions = [
                    [
                        'label' => __('Manage Stages'),
                        'icon' => 'bx bx-sitemap',
                        'url' => route('settings.dealStages.index', ['deal_pipeline' => $pipeline->id]),
                        'class' => 'btn-outline-secondary',
                    ],
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => 'editPipeline('.$pipeline->id.')',
                    ],
                    [
                        'label' => $pipeline->is_active ? __('Disable') : __('Enable'),
                        'icon' => $pipeline->is_active ? 'bx bx-block' : 'bx bx-check-circle',
                        'onclick' => 'togglePipelineStatus('.$pipeline->id.')',
                    ],
                ];

                if (! $pipeline->is_default) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => 'deletePipeline('.$pipeline->id.')',
                        'class' => 'text-danger',
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $pipeline->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['order', 'name', 'description', 'status', 'actions'])
            ->addIndexColumn()
            ->make(true);
    }

    public function getPipelineAjax(DealPipeline $deal_pipeline)
    {
        return response()->json($deal_pipeline);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:deal_pipelines,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_default'] = $request->boolean('is_default');

            if ($data['is_default']) {
                DealPipeline::where('is_default', true)->update(['is_default' => false]);
            }
            $data['position'] = (DealPipeline::max('position') ?? 0) + 1;

            DealPipeline::create($data);
            DB::commit();

            return Success::response(['message' => __('Deal Pipeline created successfully.')]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('DealPipeline Store: '.$e->getMessage());

            return Error::response(__('Failed to create pipeline.'));
        }
    }

    public function update(Request $request, DealPipeline $deal_pipeline)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:deal_pipelines,name,'.$deal_pipeline->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active', true);
            $data['is_default'] = $request->boolean('is_default');

            if ($data['is_default']) {
                DealPipeline::where('id', '!=', $deal_pipeline->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $deal_pipeline->update($data);
            DB::commit();

            return Success::response(['message' => __('Deal Pipeline updated successfully.')]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('DealPipeline Update: '.$e->getMessage());

            return Error::response(__('Failed to update pipeline.'));
        }
    }

    public function destroy(DealPipeline $deal_pipeline)
    {
        try {
            if ($deal_pipeline->is_default && DealPipeline::count() > 1) {
                return Error::response(__('Cannot delete the default pipeline. Please set another pipeline as default first.'));
            }

            if ($deal_pipeline->stages()->exists() || $deal_pipeline->deals()->exists()) {
                return Error::response(__('Cannot delete pipeline as it has associated stages or deals.'));
            }

            $deal_pipeline->delete();

            return Success::response(['message' => __('Deal Pipeline deleted successfully.')]);
        } catch (Exception $e) {
            Log::error('DealPipeline Delete: '.$e->getMessage());

            return Error::response(__('Failed to delete pipeline.'));
        }
    }

    public function updateOrder(Request $request)
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:deal_pipelines,id']);
        try {
            foreach ($request->input('order') as $index => $id) {
                DealPipeline::where('id', $id)->update(['position' => $index + 1]);
            }

            return Success::response(['message' => __('Pipeline order updated successfully.')]);
        } catch (Exception $e) {
            Log::error('DealPipeline Order Update: '.$e->getMessage());

            return Error::response(__('Failed to update pipeline order.'));
        }
    }

    public function toggleStatus(DealPipeline $deal_pipeline)
    {
        try {
            // Simple toggle - the boolean cast handles conversion
            $deal_pipeline->is_active = ! $deal_pipeline->is_active;
            $deal_pipeline->save();

            $message = $deal_pipeline->is_active
              ? __('Pipeline enabled successfully.')
              : __('Pipeline disabled successfully.');

            return Success::response(['message' => $message]);
        } catch (Exception $e) {
            Log::error('DealPipeline Toggle Status: '.$e->getMessage());

            return Error::response(__('Failed to update pipeline status.'));
        }
    }
}
