<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Settings\ModuleSettingsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\DealStage;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\LeadSource;
use Modules\CRMCore\app\Models\LeadStatus;
use Modules\CRMCore\app\Models\TaskPriority;
use Modules\CRMCore\app\Models\TaskStatus;
use Modules\CRMCore\app\Services\CRMNotificationService;
use Yajra\DataTables\Facades\DataTables;

class LeadController extends Controller
{
    protected CRMNotificationService $notificationService;

    protected ModuleSettingsService $settingsService;

    public function __construct(
        CRMNotificationService $notificationService,
        ModuleSettingsService $settingsService
    ) {
        $this->notificationService = $notificationService;
        $this->settingsService = $settingsService;
    }

    public function index()
    {
        $leadStatuses = LeadStatus::orderBy('position')->get();
        $leadSources = LeadSource::where('is_active', true)->pluck('name', 'id');

        return view('crmcore::leads.index', compact('leadStatuses', 'leadSources'));
    }

    public function getDataTableAjax(Request $request)
    {
        $query = Lead::with(['leadStatus', 'leadSource', 'assignedToUser:id,first_name,last_name'])
            ->select('leads.*');

        return DataTables::of($query)
            ->addColumn('status_name', function ($lead) {
                $color = $lead->leadStatus?->color ?? '#6c757d';
                $name = $lead->leadStatus?->name ?? 'N/A';

                return '<span class="badge" style="background-color:'.$color.'; color: #fff;">'.$name.'</span>';
            })
            ->addColumn('assigned_to', function ($lead) {
                if ($lead->assignedToUser) {
                    return view('components.datatable-user', [
                        'user' => $lead->assignedToUser,
                        'showCode' => false,
                    ])->render();
                }

                return '<span class="text-muted">'.__('Unassigned').'</span>';
            })
            ->addColumn('actions', function ($lead) {
                $actions = [
                    ['label' => __('View'), 'icon' => 'bx bx-show', 'url' => route('leads.show', $lead->id)],
                    ['label' => __('Edit'), 'icon' => 'bx bx-edit', 'class' => 'edit-lead', 'data' => ['url' => route('leads.getLeadAjax', $lead->id)]],
                    ['divider' => true],
                    ['label' => __('Delete'), 'icon' => 'bx bx-trash', 'class' => 'text-danger delete-lead', 'data' => ['id' => $lead->id, 'url' => route('leads.destroy', $lead->id)]],
                ];

                return view('components.datatable-actions', ['id' => $lead->id, 'actions' => $actions])->render();
            })
            ->rawColumns(['status_name', 'assigned_to', 'actions'])
            ->make(true);
    }

    public function getKanbanDataAjax()
    {
        $leads = Lead::with(['assignedToUser:id,first_name,last_name', 'leadStatus:id,name,color'])
            ->whereHas('leadStatus', function ($query) {
                $query->where('is_final', false);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $groupedLeads = $leads->groupBy('lead_status_id');

        return response()->json($groupedLeads);
    }

    public function getLeadAjax(Lead $lead)
    {
        $lead->load(['assignedToUser:id,first_name,last_name']);

        return response()->json($lead);
    }

    public function store(Request $request)
    {
        $validator = $this->validateLead($request);
        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            if (empty($data['lead_status_id'])) {
                $data['lead_status_id'] = LeadStatus::where('is_default', true)->firstOrFail()->id;
            }
            $lead = Lead::create($data);

            // Send notification if lead is assigned
            if (! empty($data['assigned_to_user_id']) && $lead->assignedToUser) {
                $this->notificationService->notifyLeadAssignment($lead, $lead->assignedToUser);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Lead created successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lead Store Error: '.$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to create lead.'], 500);
        }
    }

    public function update(Request $request, Lead $lead)
    {
        $validator = $this->validateLead($request, $lead->id);
        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();
            $oldAssigneeId = $lead->assigned_to_user_id;
            $data = $validator->validated();
            $lead->update($data);

            // Send notification if assignment changed
            if (isset($data['assigned_to_user_id']) &&
                $data['assigned_to_user_id'] != $oldAssigneeId &&
                $lead->assignedToUser) {
                $this->notificationService->notifyLeadAssignment($lead, $lead->assignedToUser);
            }

            DB::commit();

            return response()->json(['code' => 200, 'message' => 'Lead updated successfully.']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Lead Update Error for ID {$lead->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to update lead.'], 500);
        }
    }

    public function updateKanbanStage(Request $request, Lead $lead)
    {
        $validator = Validator::make($request->all(), [
            'lead_status_id' => 'required|exists:lead_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => 'Invalid status.'], 422);
        }

        try {
            $lead->lead_status_id = $request->input('lead_status_id');
            $lead->save();

            return response()->json(['code' => 200, 'message' => 'Lead stage updated.']);
        } catch (Exception $e) {
            Log::error("Lead Kanban Update Error for ID {$lead->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to update lead stage.'], 500);
        }
    }

    public function destroy(Lead $lead)
    {
        try {
            $lead->delete();

            return response()->json(['code' => 200, 'message' => 'Lead deleted successfully.']);
        } catch (Exception $e) {
            Log::error("Lead Delete Error for ID {$lead->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to delete lead.'], 500);
        }
    }

    public function show(Lead $lead)
    {
        $completedTaskStatusIds = TaskStatus::where('is_completed_status', true)->pluck('id');

        $lead->load([
            'leadStatus',
            'leadSource',
            'assignedToUser:id,first_name,last_name',
            'tasks' => function ($query) use ($completedTaskStatusIds) {
                $query->whereNotIn('task_status_id', $completedTaskStatusIds)
                    ->with(['status:id,name,color', 'priority:id,name,color', 'assignedToUser:id,first_name,last_name'])
                    ->orderByRaw('ISNULL(due_date) ASC, due_date ASC');
            },
        ]);

        $activities = $lead->audits()->with('user:id,first_name,last_name')->latest()->limit(20)->get();

        $taskStatuses = TaskStatus::orderBy('position')->pluck('name', 'id');
        $taskPriorities = TaskPriority::orderBy('level')->pluck('name', 'id');

        return view('crmcore::leads.show', compact(
            'lead',
            'activities',
            'taskStatuses',
            'taskPriorities'
        ));
    }

    private function validateLead(Request $request, $leadId = null)
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'contact_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'value' => 'nullable|numeric|min:0',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_status_id' => 'nullable|exists:lead_statuses,id',
            'assigned_to_user_id' => 'nullable|exists:users,id',
        ];

        if ($request->filled('contact_email')) {
            $rules['contact_email'] .= '|unique:leads,contact_email'.($leadId ? ','.$leadId : '');
        }

        return Validator::make($request->all(), $rules);
    }

    public function selectSearch(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $resultsPerPage = 15;

        $query = Lead::query()
            ->where('title', 'like', "%{$searchTerm}%") // Search by title
            ->orWhere('contact_name', 'like', "%{$searchTerm}%")
            ->orWhere('company_name', 'like', "%{$searchTerm}%")
            ->orderBy('title');

        $leads = $query->paginate($resultsPerPage, ['id', 'title', 'contact_name', 'company_name'], 'page', $page);

        $formattedLeads = $leads->map(function ($lead) {
            $text = $lead->title;
            if ($lead->contact_name) {
                $text .= ' ('.$lead->contact_name.($lead->company_name ? ' @ '.$lead->company_name : '').')';
            } elseif ($lead->company_name) {
                $text .= ' ('.$lead->company_name.')';
            }

            return ['id' => $lead->id, 'text' => $text];
        });

        return response()->json([
            'results' => $formattedLeads,
            'pagination' => ['more' => $leads->hasMorePages()],
        ]);
    }

    public function processConversion(Request $request, Lead $lead)
    {
        if ($lead->converted_at) {
            return response()->json(['code' => 400, 'message' => 'This lead has already been converted.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'contact_first_name' => 'required|string|max:255',
            'contact_last_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'company_option' => 'required|string|in:none,existing,new',
            'existing_company_id' => 'nullable|required_if:company_option,existing|exists:companies,id',
            'new_company_name' => 'nullable|required_if:company_option,new|string|max:255',
            'create_deal' => 'boolean',
            'deal_title' => 'nullable|required_if:create_deal,true|string|max:255',
            'deal_value' => 'nullable|required_if:create_deal,true|numeric|min:0',
            'deal_pipeline_id' => 'nullable|required_if:create_deal,true|exists:deal_pipelines,id',
            'deal_stage_id' => 'nullable|required_if:create_deal,true|exists:deal_stages,id',
            'deal_expected_close_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['code' => 422, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $contactData = [
                'first_name' => $request->input('contact_first_name'),
                'last_name' => $request->input('contact_last_name'),
                'email_primary' => $request->input('contact_email'),
                'phone_primary' => $request->input('contact_phone'),
                'lead_source_name' => $lead->leadSource?->name,
                'assigned_to_user_id' => $lead->assigned_to_user_id,
            ];

            $contact = null;
            if ($request->filled('contact_email')) {
                $contact = Contact::where('email_primary', $request->input('contact_email'))->first();
            }
            if (! $contact) {
                $contact = Contact::create($contactData);
            } else {
                $contact->update(array_filter($contactData));
            }

            $company = null;
            if ($request->input('company_option') === 'existing' && $request->filled('existing_company_id')) {
                $company = Company::find($request->input('existing_company_id'));
            } elseif ($request->input('company_option') === 'new' && $request->filled('new_company_name')) {
                $company = Company::create([
                    'name' => $request->input('new_company_name'),
                    'website' => $lead->company_name && filter_var('http://'.$lead->company_name, FILTER_VALIDATE_URL) ? $lead->company_name : null,
                    'assigned_to_user_id' => $lead->assigned_to_user_id,
                ]);
            }

            if ($contact && $company && ! $contact->company_id) {
                $contact->company_id = $company->id;
                $contact->save();
            }
            $deal = null;
            if ($request->boolean('create_deal')) {
                $pipelineId = $request->input('deal_pipeline_id');
                $stageId = $request->input('deal_stage_id');

                if ($pipelineId && $stageId) {
                    $stage = DealStage::where('id', $stageId)->where('pipeline_id', $pipelineId)->first();
                    if (! $stage) {
                        $defaultStage = DealStage::where('pipeline_id', $pipelineId)->where('is_default_for_pipeline', true)->first();
                        $stageId = $defaultStage ? $defaultStage->id : DealStage::where('pipeline_id', $pipelineId)->orderBy('position')->firstOrFail()->id;
                    }
                }

                $deal = Deal::create([
                    'title' => $request->input('deal_title', $lead->title),
                    'value' => $request->input('deal_value', $lead->value ?? 0),
                    'pipeline_id' => $pipelineId,
                    'deal_stage_id' => $stageId,
                    'company_id' => $company?->id,
                    'contact_id' => $contact->id,
                    'assigned_to_user_id' => $lead->assigned_to_user_id,
                    'expected_close_date' => $request->input('deal_expected_close_date'),
                ]);
            }

            $convertedStatus = LeadStatus::where('name', 'Converted')->where('is_final', true)->first();
            if (! $convertedStatus) {
                $convertedStatus = LeadStatus::firstOrCreate(
                    ['name' => 'Converted'],
                    ['color' => '#2196f3', 'position' => 99, 'is_final' => true]
                );
            }

            $lead->lead_status_id = $convertedStatus->id;
            $lead->converted_at = now();
            $lead->converted_to_contact_id = $contact->id;
            $lead->converted_to_deal_id = $deal?->id;
            $lead->save();

            DB::commit();

            $responseData = [
                'message' => 'Lead converted successfully!',
                'contact_url' => route('contacts.show', $contact->id),
            ];
            if ($company) {
                $responseData['company_url'] = route('companies.show', $company->id);
            }
            if ($deal) {
                $responseData['deal_url'] = route('deals.show', $deal->id);
            }

            return response()->json(array_merge(['code' => 200], $responseData));

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Lead Conversion Error for Lead ID {$lead->id}: ".$e->getMessage());

            return response()->json(['code' => 500, 'message' => 'Failed to convert lead: '.$e->getMessage()], 500);
        }
    }
}
