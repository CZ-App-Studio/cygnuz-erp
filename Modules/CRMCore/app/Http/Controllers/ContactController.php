<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\DealPipeline;
use Modules\CRMCore\app\Models\TaskPriority;
use Modules\CRMCore\app\Models\TaskStatus;
use Modules\CRMCore\app\Services\CRMValidationService;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{
    protected CRMValidationService $validationService;

    public function __construct(CRMValidationService $validationService)
    {
        $this->validationService = $validationService;
        // Permission middleware will be added when implementing RBAC
    }

    public function index()
    {
        return view('crmcore::contacts.index');
    }

    public function getDataAjax(Request $request)
    {
        $query = Contact::with(['company:id,name', 'assignedToUser:id,first_name,last_name'])
            ->select('contacts.*');

        return DataTables::of($query)
            ->addColumn('full_name', fn ($contact) => $contact->full_name) // Using the accessor
            ->addColumn('company_name', fn ($contact) => $contact->company?->name ?? 'N/A')
            ->addColumn('assigned_to', function ($contact) {
                if ($contact->assignedToUser) {
                    return view('components.datatable-user', [
                        'user' => $contact->assignedToUser,
                        'showCode' => false,
                    ])->render();
                }

                return 'N/A';
            })
            ->editColumn('is_active', function ($contact) {
                return view('components.datatable-status-toggle', [
                    'id' => $contact->id,
                    'checked' => $contact->is_active,
                    'url' => route('contacts.toggleStatus', $contact->id),
                ])->render();
            })
            ->addColumn('actions', function ($contact) {
                $actions = [
                    ['label' => __('View'), 'icon' => 'bx bx-show', 'url' => route('contacts.show', $contact->id)],
                    ['label' => __('Edit'), 'icon' => 'bx bx-edit', 'url' => route('contacts.edit', $contact->id)],
                    ['divider' => true],
                    ['label' => __('Delete'), 'icon' => 'bx bx-trash', 'class' => 'text-danger delete-contact', 'data' => ['id' => $contact->id, 'url' => route('contacts.destroy', $contact->id)]],
                ];

                return view('components.datatable-actions', ['id' => $contact->id, 'actions' => $actions])->render();
            })
            ->rawColumns(['is_active', 'actions', 'assigned_to'])
            ->make(true);
    }

    public function create()
    {
        $companyId = request()->query('company_id');
        $company = $companyId ? Company::find($companyId) : null;

        return view('crmcore::contacts.create', compact('company'));
    }

    public function store(Request $request)
    {
        // Get validation rules from settings-aware service
        $rules = $this->validationService->getContactValidationRules();
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicates based on settings
        $duplicates = $this->validationService->checkContactDuplicates($validator->validated());
        if (! empty($duplicates)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Potential duplicate contact detected'),
                'duplicates' => $duplicates,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $data = $validator->validated();
            // Apply default values from settings
            $defaults = $this->validationService->getEntityDefaults('contact');
            $data = array_merge($defaults, $data);

            $contact = Contact::create($data);
            DB::commit();

            return Success::response(['message' => __('Contact created successfully.')]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Contact Store Error: '.$e->getMessage());

            return Error::response(__('Failed to create contact.'));
        }
    }

    public function show(Contact $contact)
    {
        // $this->authorize('view', $contact); // If using policies

        $completedTaskStatusIds = TaskStatus::where('is_completed_status', true)->pluck('id');

        $contact->load([
            'company', // Company this contact belongs to
            'deals' => function ($query) { // Deals where this contact is primary
                $query->with(['dealStage:id,name,color', 'pipeline:id,name', 'assignedToUser:id,first_name,last_name', 'company:id,name'])
                    ->orderBy('expected_close_date', 'desc');
            },
            'tasks' => function ($query) use ($completedTaskStatusIds) { // Tasks related to this contact
                $query->whereNotIn('task_status_id', $completedTaskStatusIds) // Show open tasks
                    ->with(['status:id,name,color', 'priority:id,name,color', 'assignedToUser:id,first_name,last_name'])
                    ->orderByRaw('ISNULL(due_date) ASC, due_date ASC');
            },
            'assignedToUser:id,first_name,last_name',
        ]);

        $activities = $contact->audits()->with('user:id,first_name,last_name')->latest()->limit(20)->get();

        // Data for offcanvas forms
        $taskStatuses = TaskStatus::orderBy('position')->pluck('name', 'id');
        $taskPriorities = TaskPriority::orderBy('level')->pluck('name', 'id');
        $allPipelinesForForm = DealPipeline::orderBy('position')->pluck('name', 'id');
        $pipelinesWithStages = DealPipeline::with([
            'stages' => function ($query) {
                $query->orderBy('position');
            },
        ])->orderBy('position')->get()->mapWithKeys(function ($pipeline) {
            return [
                $pipeline->id => [
                    'name' => $pipeline->name,
                    'stages' => $pipeline->stages->mapWithKeys(function ($stage) {
                        return [
                            $stage->id => [
                                'name' => $stage->name,
                                'color' => $stage->color,
                                'is_won_stage' => $stage->is_won_stage,
                                'is_lost_stage' => $stage->is_lost_stage,
                            ],
                        ];
                    }),
                ],
            ];
        });
        $initialPipelineIdData = (DealPipeline::where('is_default', true)->first() ?? DealPipeline::orderBy('position')->first())->id ?? null;

        return view('crmcore::contacts.show', compact(
            'contact',
            'activities',
            'taskStatuses',
            'taskPriorities',
            'allPipelinesForForm',
            'pipelinesWithStages',
            'initialPipelineIdData'
        ));
    }

    public function edit(Contact $contact)
    {
        return view('crmcore::contacts.edit', compact('contact'));
    }

    public function update(Request $request, Contact $contact)
    {
        // Get validation rules from settings-aware service
        $rules = $this->validationService->getContactValidationRules($contact->id);
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => __('Validation failed'),
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicates based on settings (excluding current contact)
        $duplicates = $this->validationService->checkContactDuplicates($validator->validated(), $contact->id);
        if (! empty($duplicates)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Potential duplicate contact detected'),
                'duplicates' => $duplicates,
            ], 422);
        }

        try {
            DB::beginTransaction();
            $contact->update($validator->validated());
            DB::commit();

            return Success::response(['message' => __('Contact updated successfully.')]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Contact Update Error for ID {$contact->id}: ".$e->getMessage());

            return Error::response(__('Failed to update contact.'));
        }
    }

    public function destroy(Contact $contact)
    {
        try {
            $contact->delete();

            return Success::response(['message' => 'Contact deleted successfully.']);
        } catch (Exception $e) {
            Log::error("Contact Delete Error for ID {$contact->id}: ".$e->getMessage());

            return Error::response('Failed to delete contact.', 500);
        }
    }

    public function toggleStatus(Request $request, Contact $contact)
    {
        try {
            $contact->is_active = ! $contact->is_active;
            $contact->save();

            return Success::response(['message' => 'Status updated successfully.', 'is_active' => $contact->is_active]);
        } catch (Exception $e) {
            Log::error("Contact Toggle Status Error for ID {$contact->id}: ".$e->getMessage());

            return Error::response('Failed to update status.', 500);
        }
    }

    public function selectSearch(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $resultsPerPage = 15;
        $companyId = $request->input('company_id'); // Optional: filter contacts by company

        $query = Contact::where('is_active', true)
            ->where(function ($q) use ($searchTerm) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$searchTerm}%")
                    ->orWhere('email_primary', 'like', "%{$searchTerm}%")
                    ->orWhere('phone_primary', 'like', "%{$searchTerm}%");
            });

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $query->orderBy('first_name')->orderBy('last_name');

        $contacts = $query->paginate($resultsPerPage, ['id', 'first_name', 'last_name'], 'page', $page);

        $formattedContacts = $contacts->map(function ($contact) {
            return ['id' => $contact->id, 'text' => $contact->getFullNameAttribute()]; // Using accessor
        });

        return response()->json([
            'results' => $formattedContacts,
            'pagination' => ['more' => $contacts->hasMorePages()],
        ]);
    }

    public function getDetailsAjax(Contact $contact)
    {
        $contact->load('company'); // Eager-load company details

        return response()->json($contact);
    }
}
