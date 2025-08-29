<?php

namespace Modules\CRMCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Http\Controllers\Api\BaseApiController;
use Modules\CRMCore\app\Http\Resources\DealResource;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\DealPipeline;
use Modules\CRMCore\app\Models\DealStage;
use Carbon\Carbon;

class DealController extends BaseApiController
{
    /**
     * Display a listing of deals
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Deal::with(['pipeline', 'stage', 'contact', 'company', 'assignedTo', 'lead']);

            // Filter by pipeline
            if ($request->has('pipeline_id')) {
                $query->where('pipeline_id', $request->pipeline_id);
            }

            // Filter by stage
            if ($request->has('stage_id')) {
                $query->where('deal_stage_id', $request->stage_id);
            }

            // Filter by assigned user
            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            // Filter by status
            if ($request->has('status')) {
                switch ($request->status) {
                    case 'open':
                        $query->whereNull('won_at')->whereNull('lost_at');
                        break;
                    case 'won':
                        $query->whereNotNull('won_at');
                        break;
                    case 'lost':
                        $query->whereNotNull('lost_at');
                        break;
                }
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $deals = $query->paginate($request->input('per_page', 20));

            return $this->successResponse(
                DealResource::collection($deals),
                'Deals retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve deals', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created deal
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'pipeline_id' => 'required|exists:deal_pipelines,id',
            'deal_stage_id' => 'required|exists:deal_stages,id',
            'expected_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by_id'] = Auth::id();
            $data['tenant_id'] = $request->header('X-Tenant-Id');
            $data['currency'] = $data['currency'] ?? 'USD';

            $deal = Deal::create($data);
            $deal->load(['pipeline', 'stage', 'contact', 'company', 'assignedTo']);

            DB::commit();

            return $this->successResponse(
                new DealResource($deal),
                'Deal created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create deal', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified deal
     */
    public function show($id): JsonResponse
    {
        try {
            $deal = Deal::with(['pipeline', 'stage', 'contact', 'company', 'assignedTo', 'lead'])
                ->findOrFail($id);

            return $this->successResponse(
                new DealResource($deal),
                'Deal retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Deal not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve deal', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified deal
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'pipeline_id' => 'sometimes|required|exists:deal_pipelines,id',
            'deal_stage_id' => 'sometimes|required|exists:deal_stages,id',
            'expected_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
            'assigned_to' => 'nullable|exists:users,id',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $deal = Deal::findOrFail($id);
            
            $data = $request->all();
            $data['updated_by_id'] = Auth::id();
            
            $deal->update($data);
            $deal->load(['pipeline', 'stage', 'contact', 'company', 'assignedTo']);

            return $this->successResponse(
                new DealResource($deal),
                'Deal updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Deal not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update deal', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified deal
     */
    public function destroy($id): JsonResponse
    {
        try {
            $deal = Deal::findOrFail($id);
            $deal->delete();

            return $this->successResponse(null, 'Deal deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Deal not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete deal', $e->getMessage(), 500);
        }
    }

    /**
     * Mark deal as won
     */
    public function markAsWon(Request $request, $id): JsonResponse
    {
        try {
            $deal = Deal::findOrFail($id);
            
            if ($deal->won_at) {
                return $this->errorResponse('Deal already marked as won', null, 400);
            }
            
            if ($deal->lost_at) {
                return $this->errorResponse('Cannot mark lost deal as won', null, 400);
            }

            $deal->won_at = now();
            $deal->closed_at = now();
            $deal->probability = 100;
            $deal->save();

            $deal->load(['pipeline', 'stage', 'contact', 'company', 'assignedTo']);

            return $this->successResponse(
                new DealResource($deal),
                'Deal marked as won successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Deal not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark deal as won', $e->getMessage(), 500);
        }
    }

    /**
     * Mark deal as lost
     */
    public function markAsLost(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lost_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $deal = Deal::findOrFail($id);
            
            if ($deal->lost_at) {
                return $this->errorResponse('Deal already marked as lost', null, 400);
            }
            
            if ($deal->won_at) {
                return $this->errorResponse('Cannot mark won deal as lost', null, 400);
            }

            $deal->lost_at = now();
            $deal->closed_at = now();
            $deal->lost_reason = $request->lost_reason;
            $deal->probability = 0;
            $deal->save();

            $deal->load(['pipeline', 'stage', 'contact', 'company', 'assignedTo']);

            return $this->successResponse(
                new DealResource($deal),
                'Deal marked as lost successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Deal not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark deal as lost', $e->getMessage(), 500);
        }
    }

    /**
     * Move deal to another stage
     */
    public function moveStage(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deal_stage_id' => 'required|exists:deal_stages,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $deal = Deal::findOrFail($id);
            $newStage = DealStage::findOrFail($request->deal_stage_id);

            // Check if stage belongs to same pipeline
            if ($newStage->pipeline_id != $deal->pipeline_id) {
                return $this->errorResponse('Stage does not belong to deal pipeline', null, 400);
            }

            $deal->deal_stage_id = $request->deal_stage_id;
            $deal->probability = $newStage->probability ?? $deal->probability;
            $deal->save();

            $deal->load(['pipeline', 'stage', 'contact', 'company', 'assignedTo']);

            return $this->successResponse(
                new DealResource($deal),
                'Deal stage updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Deal or stage not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to move deal stage', $e->getMessage(), 500);
        }
    }

    /**
     * Get deal statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Deal::query();

            // Apply date filters
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $stats = [
                'total_deals' => $query->count(),
                'open_deals' => (clone $query)->whereNull('won_at')->whereNull('lost_at')->count(),
                'won_deals' => (clone $query)->whereNotNull('won_at')->count(),
                'lost_deals' => (clone $query)->whereNotNull('lost_at')->count(),
                'total_value' => (clone $query)->sum('value'),
                'won_value' => (clone $query)->whereNotNull('won_at')->sum('value'),
                'average_deal_value' => $query->count() > 0 ? (clone $query)->avg('value') : 0,
                'win_rate' => $query->count() > 0 
                    ? round(((clone $query)->whereNotNull('won_at')->count() / $query->count()) * 100, 2)
                    : 0,
                'deals_by_pipeline' => DealPipeline::where('is_active', true)->withCount(['deals' => function ($q) use ($query) {
                    $q->whereIn('id', (clone $query)->pluck('id'));
                }])->get()->map(function ($pipeline) {
                    return [
                        'id' => $pipeline->id,
                        'name' => $pipeline->name,
                        'count' => $pipeline->deals_count,
                    ];
                }),
                'deals_by_stage' => DealStage::withCount(['deals' => function ($q) use ($query) {
                    $q->whereIn('id', (clone $query)->pluck('id'));
                }])->get()->map(function ($stage) {
                    return [
                        'id' => $stage->id,
                        'name' => $stage->name,
                        'color' => $stage->color,
                        'count' => $stage->deals_count,
                    ];
                }),
                'recent_wins' => (clone $query)->whereNotNull('won_at')->latest('won_at')->take(5)->get()->map(function ($deal) {
                    return [
                        'id' => $deal->id,
                        'title' => $deal->title,
                        'value' => $deal->value,
                        'won_at' => $deal->won_at,
                    ];
                }),
            ];

            return $this->successResponse($stats, 'Deal statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }

    /**
     * Get pipelines
     */
    public function getPipelines(): JsonResponse
    {
        try {
            $pipelines = DealPipeline::where('is_active', true)
                ->with(['stages' => function ($query) {
                    $query->orderBy('position');
                }])
                ->orderBy('name')
                ->get();

            return $this->successResponse($pipelines, 'Pipelines retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve pipelines', $e->getMessage(), 500);
        }
    }
}