<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\DealPipeline;
use Modules\CRMCore\app\Models\DealStage;
use Modules\CRMCore\app\Models\TaskPriority;
use Modules\CRMCore\app\Models\TaskStatus;
use Yajra\DataTables\Facades\DataTables;

class DealController extends Controller
{
    public function __construct()
    {
        /* $this->middleware('permission:view_deals')->only(['index', 'getKanbanDataAjax', 'getDataTableAjax', 'show', 'selectSearch']);
         $this->middleware('permission:create_deals')->only(['store']);
         $this->middleware('permission:edit_deals')->only(['update', 'updateKanbanStage']);
         $this->middleware('permission:delete_deals')->only(['destroy']);
         $this->middleware('permission:change_deal_stage')->only(['updateKanbanStage']);*/
    }

    /**
     * Display the main deals view (Kanban/List).
     */
    public function index()
    {
        $pipelines = DealPipeline::with(['stages' => function ($query) {
            $query->orderBy('position');
        }])->orderBy('position')->get();

        $defaultPipeline = $pipelines->firstWhere('is_default', true) ?? $pipelines->first();
        $allPipelinesForForm = $pipelines->pluck('name', 'id');

        return view('crmcore::deals.index', compact(
            'pipelines',
            'defaultPipeline',
            'allPipelinesForForm'
        ));
    }

    /**
     * Fetch data to populate the Kanban board.
     */
    public function getKanbanDataAjax(Request $request)
    {
        $pipelineId = $request->input('pipeline_id');
        if (! $pipelineId) {
            $defaultPipeline = DealPipeline::where('is_default', true)->first() ?? DealPipeline::orderBy('position')->first();
            $pipelineId = $defaultPipeline?->id;
        }

        if (! $pipelineId) {
            return response()->json([]);
        }

        $deals = Deal::with(['assignedToUser:id,first_name,last_name', 'company:id,name', 'contact:id,first_name,last_name', 'dealStage:id,name,color'])
            ->where('pipeline_id', $pipelineId)
            ->whereHas('dealStage', function ($query) {
                $query->where('is_won_stage', false)->where('is_lost_stage', false);
            })
            ->orderBy('expected_close_date', 'asc')
            ->get();

        $groupedDeals = $deals->groupBy('deal_stage_id');

        return response()->json($groupedDeals);
    }

    /**
     * Process DataTables ajax request for the list view (Optional).
     */
    public function getDataTableAjax(Request $request)
    {
        $query = Deal::with(['dealStage', 'pipeline', 'company', 'contact', 'assignedToUser:id,first_name,last_name,profile_picture'])
            ->select('deals.*');

        return DataTables::of($query)
            ->addColumn('pipeline_name', fn ($deal) => $deal->pipeline?->name ?? 'N/A')
            ->addColumn('stage_name', function ($deal) {
                $color = $deal->dealStage?->color ?? '#6c757d';
                $name = $deal->dealStage?->name ?? 'N/A';

                return '<span class="badge" style="background-color:'.$color.'; color: #fff;">'.$name.'</span>';
            })
            ->addColumn('company_name', fn ($deal) => $deal->company?->name ?? 'N/A')
            ->addColumn('contact_name', fn ($deal) => $deal->contact?->getFullNameAttribute() ?? 'N/A')
            ->addColumn('assigned_to', function ($deal) {
                return view('components.datatable-user', ['user' => $deal->assignedToUser])->render();
            })
            ->editColumn('value', fn ($deal) => '$'.number_format($deal->value, 2))
            ->editColumn('expected_close_date', fn ($deal) => $deal->expected_close_date ? $deal->expected_close_date->format('d M Y') : '-')
            ->addColumn('actions', function ($deal) {
                return view('components.datatable-actions', [
                    'id' => $deal->id,
                    'actions' => [
                        ['label' => __('View'), 'icon' => 'bx bx-show', 'url' => route('deals.show', $deal->id)],
                        ['label' => __('Edit'), 'icon' => 'bx bx-edit', 'class' => 'edit-deal', 'attributes' => 'data-url='.route('deals.getDealAjax', $deal->id)],
                        ['label' => __('Delete'), 'icon' => 'bx bx-trash', 'class' => 'delete-deal text-danger', 'attributes' => 'data-id='.$deal->id.' data-url='.route('deals.destroy', $deal->id)],
                    ],
                ])->render();
            })
            ->rawColumns(['stage_name', 'assigned_to', 'actions'])
            ->make(true);
    }

    /**
     * Fetch a single deal's data for the offcanvas edit form.
     */
    public function getDealAjax(Deal $deal)
    {
        $deal->load([
            'company:id,name',
            'contact:id,first_name,last_name',
            'assignedToUser:id,first_name,last_name',
            'dealStage:id,name,is_lost_stage,is_won_stage',
        ]);

        return response()->json($deal);
    }

    /**
     * Store a newly created deal from the offcanvas form.
     */
    public function store(Request $request)
    {
        $validator = $this->validateDeal($request);
        if ($validator->fails()) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            if (isset($data['pipeline_id']) && ! isset($data['deal_stage_id'])) {
                $defaultStage = DealStage::where('pipeline_id', $data['pipeline_id'])->where('is_default_for_pipeline', true)->first();
                $data['deal_stage_id'] = $defaultStage ? $defaultStage->id : DealStage::where('pipeline_id', $data['pipeline_id'])->orderBy('position')->firstOrFail()->id;
            } elseif (! isset($data['pipeline_id'])) {
                $defaultPipeline = DealPipeline::where('is_default', true)->first() ?? DealPipeline::orderBy('position')->firstOrFail();
                $data['pipeline_id'] = $defaultPipeline->id;
                $defaultStage = DealStage::where('pipeline_id', $data['pipeline_id'])->where('is_default_for_pipeline', true)->first();
                $data['deal_stage_id'] = $defaultStage ? $defaultStage->id : DealStage::where('pipeline_id', $data['pipeline_id'])->orderBy('position')->firstOrFail()->id;
            }

            Deal::create($data);
            DB::commit();

            return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Deal created successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Deal Store Error: '.$e->getMessage());

            return response()->json(['code' => 500, 'status' => 'error', 'message' => 'Failed to create deal.'], 500);
        }
    }

    /**
     * Update the specified deal from the offcanvas form.
     */
    public function update(Request $request, Deal $deal)
    {
        $validator = $this->validateDeal($request, $deal->id);
        if ($validator->fails()) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $deal->update($validator->validated());
            DB::commit();

            return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Deal updated successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Deal Update Error for ID {$deal->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'status' => 'error', 'message' => 'Failed to update deal.'], 500);
        }
    }

    /**
     * Handle updating a deal's stage from Kanban drag-and-drop.
     */
    public function updateKanbanStage(Request $request, Deal $deal)
    {
        $validator = Validator::make($request->all(), [
            'deal_stage_id' => 'required|exists:deal_stages,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Invalid stage provided.'], 400);
        }

        try {
            $newStageId = $request->input('deal_stage_id');
            $newStage = DealStage::find($newStageId);

            if ($newStage->pipeline_id !== $deal->pipeline_id) {
                return response()->json(['code' => 400, 'status' => 'error', 'message' => 'Cannot move deal to a stage in a different pipeline via Kanban.'], 400);
            }

            $deal->deal_stage_id = $newStageId;

            if ($newStage->is_won_stage || $newStage->is_lost_stage) {
                $deal->actual_close_date = now();
                if ($newStage->is_lost_stage && $request->filled('lost_reason')) {
                    $deal->lost_reason = $request->input('lost_reason');
                }
            } else {
                if ($deal->dealStage && ($deal->dealStage->is_won_stage || $deal->dealStage->is_lost_stage)) {
                    $deal->actual_close_date = null;
                    $deal->lost_reason = null;
                }
            }

            $deal->save();

            return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Deal stage updated.']);
        } catch (Exception $e) {
            Log::error("Deal Kanban Update Error for ID {$deal->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'status' => 'error', 'message' => 'Failed to update deal stage.'], 500);
        }
    }

    /**
     * Remove the specified deal from storage.
     */
    public function destroy(Deal $deal)
    {
        try {
            $deal->delete();

            return response()->json(['code' => 200, 'status' => 'success', 'message' => 'Deal deleted successfully.']);
        } catch (Exception $e) {
            Log::error("Deal Delete Error for ID {$deal->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'status' => 'error', 'message' => 'Failed to delete deal.'], 500);
        }
    }

    /**
     * Display the specified deal details on a full page.
     */
    public function show(Deal $deal)
    {
        $completedTaskStatusIds = TaskStatus::where('is_completed_status', true)->pluck('id');

        $deal->load([
            'pipeline',
            'dealStage',
            'contact:id,first_name,last_name,email_primary',
            'company:id,name',
            'assignedToUser:id,first_name,last_name,profile_picture',
            'tasks' => function ($query) use ($completedTaskStatusIds) {
                $query->whereNotIn('task_status_id', $completedTaskStatusIds)
                    ->with(['status:id,name,color', 'priority:id,name,color', 'assignedToUser:id,first_name,last_name,profile_picture'])
                    ->orderByRaw('ISNULL(due_date) ASC, due_date ASC');
            },
        ]);

        $activities = $deal->audits()->with('user:id,first_name,last_name')->latest()->limit(20)->get();

        $taskStatuses = TaskStatus::orderBy('position')->pluck('name', 'id');
        $taskPriorities = TaskPriority::orderBy('level')->pluck('name', 'id');

        $allPipelinesForForm = DealPipeline::orderBy('position')->pluck('name', 'id');
        $pipelinesWithStages = DealPipeline::with(['stages' => function ($query) {
            $query->orderBy('position');
        }])->orderBy('position')->get()->mapWithKeys(function ($pipeline) {
            return [$pipeline->id => [
                'name' => $pipeline->name,
                'stages' => $pipeline->stages->mapWithKeys(function ($stage) {
                    return [$stage->id => [
                        'name' => $stage->name,
                        'color' => $stage->color,
                        'is_won_stage' => $stage->is_won_stage,
                        'is_lost_stage' => $stage->is_lost_stage,
                    ]];
                }),
            ]];
        });

        return view('crmcore::deals.show', compact(
            'deal',
            'activities',
            'taskStatuses',
            'taskPriorities',
            'allPipelinesForForm',
            'pipelinesWithStages'
        ));
    }

    /**
     * Reusable validation helper for deals.
     */
    private function validateDeal(Request $request, $dealId = null)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'pipeline_id' => 'required|exists:deal_pipelines,id',
            'deal_stage_id' => 'required|exists:deal_stages,id',
            'company_id' => 'nullable|exists:companies,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'lost_reason' => 'nullable|string|max:1000',
        ];

        return Validator::make($request->all(), $rules)->after(function ($validator) use ($request) {
            if ($request->filled('pipeline_id') && $request->filled('deal_stage_id')) {
                $stage = DealStage::find($request->input('deal_stage_id'));
                if ($stage && $stage->pipeline_id != $request->input('pipeline_id')) {
                    $validator->errors()->add('deal_stage_id', 'The selected stage does not belong to the chosen pipeline.');
                }
            }
        });
    }

    public function selectSearch(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $resultsPerPage = 15;

        $query = Deal::with(['company:id,name', 'contact:id,first_name,last_name'])
            ->where('title', 'like', "%{$searchTerm}%")
            ->orderBy('title');

        $deals = $query->paginate($resultsPerPage, ['id', 'title', 'company_id', 'contact_id'], 'page', $page);

        $formattedDeals = $deals->map(function ($deal) {
            $text = $deal->title;
            $relatedInfo = [];
            if ($deal->contact) {
                $relatedInfo[] = $deal->contact->getFullNameAttribute();
            }
            if ($deal->company) {
                $relatedInfo[] = $deal->company->name;
            }
            if (! empty($relatedInfo)) {
                $text .= ' ('.implode(' @ ', $relatedInfo).')';
            }

            return ['id' => $deal->id, 'text' => $text];
        });

        return response()->json([
            'results' => $formattedDeals,
            'pagination' => ['more' => $deals->hasMorePages()],
        ]);
    }
}
