<?php

namespace Modules\CRMCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Http\Controllers\Api\BaseApiController;
use Modules\CRMCore\app\Http\Resources\LeadResource;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\LeadSource;
use Modules\CRMCore\app\Models\LeadStatus;

class LeadController extends BaseApiController
{
    /**
     * Display a listing of leads
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Lead::with(['status', 'source', 'assignedTo', 'contact', 'company']);

            // Filter by status
            if ($request->has('status_id')) {
                $query->where('lead_status_id', $request->status_id);
            }

            // Filter by source
            if ($request->has('source_id')) {
                $query->where('lead_source_id', $request->source_id);
            }

            // Filter by assigned user
            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
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
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            // Filter by conversion status
            if ($request->has('converted')) {
                if ($request->converted == 'true') {
                    $query->whereNotNull('converted_to_deal_id');
                } else {
                    $query->whereNull('converted_to_deal_id');
                }
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $leads = $query->paginate($request->input('per_page', 20));

            return $this->successResponse(
                LeadResource::collection($leads),
                'Leads retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve leads', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created lead
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'lead_status_id' => 'nullable|exists:lead_statuses,id',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by_id'] = Auth::id();
            $data['tenant_id'] = $request->header('X-Tenant-Id');

            // Set default status if not provided
            if (! isset($data['lead_status_id'])) {
                $defaultStatus = LeadStatus::where('is_default', true)->first();
                if ($defaultStatus) {
                    $data['lead_status_id'] = $defaultStatus->id;
                }
            }

            $lead = Lead::create($data);
            $lead->load(['status', 'source', 'assignedTo', 'contact', 'company']);

            DB::commit();

            return $this->successResponse(
                new LeadResource($lead),
                'Lead created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to create lead', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified lead
     */
    public function show($id): JsonResponse
    {
        try {
            $lead = Lead::with(['status', 'source', 'assignedTo', 'contact', 'company'])
                ->findOrFail($id);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Lead not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve lead', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified lead
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'lead_status_id' => 'nullable|exists:lead_statuses,id',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_value' => 'nullable|numeric|min:0',
            'assigned_to' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'website' => 'nullable|url|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tags' => 'nullable|array',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $lead = Lead::findOrFail($id);

            $data = $request->all();
            $data['updated_by_id'] = Auth::id();

            $lead->update($data);
            $lead->load(['status', 'source', 'assignedTo', 'contact', 'company']);

            return $this->successResponse(
                new LeadResource($lead),
                'Lead updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Lead not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update lead', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified lead
     */
    public function destroy($id): JsonResponse
    {
        try {
            $lead = Lead::findOrFail($id);

            // Check if lead is converted
            if ($lead->converted_to_deal_id) {
                return $this->errorResponse('Cannot delete converted lead', null, 400);
            }

            $lead->delete();

            return $this->successResponse(null, 'Lead deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Lead not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete lead', $e->getMessage(), 500);
        }
    }

    /**
     * Convert lead to deal
     */
    public function convertToDeal(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'deal_pipeline_id' => 'required|exists:deal_pipelines,id',
            'deal_stage_id' => 'required|exists:deal_stages,id',
            'value' => 'nullable|numeric|min:0',
            'expected_close_date' => 'nullable|date',
            'probability' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $lead = Lead::findOrFail($id);

            // Check if already converted
            if ($lead->converted_to_deal_id) {
                return $this->errorResponse('Lead already converted to deal', null, 400);
            }

            // Create deal from lead
            $deal = Deal::create([
                'title' => $lead->name,
                'description' => $lead->description,
                'value' => $request->value ?? $lead->lead_value,
                'currency' => 'USD',
                'deal_pipeline_id' => $request->deal_pipeline_id,
                'deal_stage_id' => $request->deal_stage_id,
                'expected_close_date' => $request->expected_close_date,
                'probability' => $request->probability ?? 0,
                'contact_id' => $lead->contact_id,
                'company_id' => $lead->company_id,
                'lead_id' => $lead->id,
                'assigned_to' => $lead->assigned_to,
                'created_by_id' => Auth::id(),
                'tenant_id' => $request->header('X-Tenant-Id'),
            ]);

            // Update lead with conversion info
            $lead->converted_to_deal_id = $deal->id;
            $lead->converted_at = now();
            $lead->save();

            DB::commit();

            $deal->load(['pipeline', 'stage', 'contact', 'company', 'assignedTo']);

            return $this->successResponse([
                'lead' => new LeadResource($lead),
                'deal' => new \Modules\CRMCore\app\Http\Resources\DealResource($deal),
            ], 'Lead converted to deal successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return $this->notFoundResponse('Lead not found');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to convert lead', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk update lead status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lead_ids' => 'required|array',
            'lead_ids.*' => 'exists:leads,id',
            'lead_status_id' => 'required|exists:lead_statuses,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            Lead::whereIn('id', $request->lead_ids)
                ->update([
                    'lead_status_id' => $request->lead_status_id,
                    'updated_by_id' => Auth::id(),
                    'updated_at' => now(),
                ]);

            return $this->successResponse(null, 'Lead statuses updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update lead statuses', $e->getMessage(), 500);
        }
    }

    /**
     * Get lead statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Lead::query();

            // Apply date filters
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $stats = [
                'total_leads' => $query->count(),
                'converted_leads' => (clone $query)->whereNotNull('converted_to_deal_id')->count(),
                'total_value' => (clone $query)->sum('lead_value'),
                'conversion_rate' => $query->count() > 0
                    ? round(((clone $query)->whereNotNull('converted_to_deal_id')->count() / $query->count()) * 100, 2)
                    : 0,
                'leads_by_status' => LeadStatus::withCount(['leads' => function ($q) use ($query) {
                    $q->whereIn('id', (clone $query)->pluck('id'));
                }])->get()->map(function ($status) {
                    return [
                        'id' => $status->id,
                        'name' => $status->name,
                        'color' => $status->color,
                        'count' => $status->leads_count,
                    ];
                }),
                'leads_by_source' => LeadSource::withCount(['leads' => function ($q) use ($query) {
                    $q->whereIn('id', (clone $query)->pluck('id'));
                }])->get()->map(function ($source) {
                    return [
                        'id' => $source->id,
                        'name' => $source->name,
                        'count' => $source->leads_count,
                    ];
                }),
                'recent_leads' => (clone $query)->latest()->take(5)->get()->map(function ($lead) {
                    return [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'email' => $lead->email,
                        'value' => $lead->lead_value,
                        'created_at' => $lead->created_at,
                    ];
                }),
            ];

            return $this->successResponse($stats, 'Lead statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }

    /**
     * Get lead statuses
     */
    public function getStatuses(): JsonResponse
    {
        try {
            $statuses = LeadStatus::orderBy('position')->get();

            return $this->successResponse($statuses, 'Lead statuses retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statuses', $e->getMessage(), 500);
        }
    }

    /**
     * Get lead sources
     */
    public function getSources(): JsonResponse
    {
        try {
            $sources = LeadSource::orderBy('name')->get();

            return $this->successResponse($sources, 'Lead sources retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve sources', $e->getMessage(), 500);
        }
    }
}
