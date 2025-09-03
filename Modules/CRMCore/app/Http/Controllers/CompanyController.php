<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Services\Settings\ModuleSettingsService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\TaskStatus;
use Yajra\DataTables\Facades\DataTables;

class CompanyController extends Controller
{
    protected ModuleSettingsService $settingsService;

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Display a listing of the companies.
     */
    public function index()
    {
        // URLs for JS
        $ajaxUrl = route('companies.ajax');

        // Get display settings
        $itemsPerPage = $this->settingsService->get('CRMCore', 'items_per_page', 25);
        $showActivityTimeline = $this->settingsService->get('CRMCore', 'show_activity_timeline', true);

        return view('crmcore::companies.index', compact('ajaxUrl', 'itemsPerPage', 'showActivityTimeline'));
    }

    /**
     * Process DataTables ajax request.
     */
    public function getDataAjax(Request $request)
    {
        // Ensure user has permission to view companies if using a policy
        // $this->authorize('viewAny', Company::class);

        $query = Company::with(['assignedToUser']) // Eager load for assigned user
            ->select('companies.*');

        // Example of handling search from DataTables
        if ($request->filled('search.value')) {
            $searchValue = $request->input('search.value');
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email_office', 'like', "%{$searchValue}%")
                    ->orWhere('phone_office', 'like', "%{$searchValue}%")
                    ->orWhere('website', 'like', "%{$searchValue}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('assigned_to', function ($company) {
                if ($company->assignedToUser) {
                    return view('components.datatable-user', [
                        'user' => $company->assignedToUser,
                        'showCode' => false,
                        'avatarSize' => 'xs',
                    ])->render();
                }

                return '<span class="text-muted">-</span>';
            })
            ->editColumn('is_active', function ($company) {
                return view('components.datatable-status-toggle', [
                    'id' => $company->id,
                    'checked' => $company->is_active,
                    'url' => route('companies.toggleStatus', $company->id),
                ])->render();
            })
            ->addColumn('actions', function ($company) {
                $actions = [
                    [
                        'label' => __('View'),
                        'icon' => 'bx bx-show',
                        'url' => route('companies.show', $company->id),
                        'class' => '',
                    ],
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-pencil',
                        'url' => route('companies.edit', $company->id),
                        'class' => '',
                    ],
                    [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteCompany({$company->id})",
                        'class' => 'text-danger delete-company',
                        'data' => [
                            'id' => $company->id,
                            'contacts-count' => $company->contacts()->count(),
                        ],
                    ],
                ];

                return view('components.datatable-actions', [
                    'id' => $company->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['assigned_to', 'is_active', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new company.
     */
    public function create()
    {
        return view('crmcore::companies.create');
    }

    /**
     * Store a newly created company in storage.
     */
    public function store(Request $request)
    {
        // $this->authorize('create', Company::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone_office' => 'nullable|string|max:50',
            'email_office' => 'nullable|email|max:255|unique:companies,email_office'.(isset($request->tenant_id) ? ',NULL,id,tenant_id,'.$request->tenant_id : ''), // Be mindful of tenant uniqueness if applicable
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'tenant_id' => 'nullable|string|max:191', // If you are handling tenant_id manually
        ]);

        if ($validator->fails()) {
            return redirect()->route('companies.create')
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active'); // Ensure boolean

            Company::create($data);
            DB::commit();

            return redirect()->route('companies.index')->with('success', 'Company created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Company Store Error: '.$e->getMessage());

            return redirect()->route('companies.create')
                ->with('error', 'Failed to create company: '.$e->getMessage()) // Provide more user-friendly message in production
                ->withInput();
        }
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company)
    {
        // $this->authorize('view', $company); // If you have policies

        $completedTaskStatusIds = TaskStatus::where('is_completed_status', true)->pluck('id');

        $company->load([
            'contacts' => function ($query) {
                $query->orderBy('first_name')->orderBy('last_name');
            },
            'deals' => function ($query) {
                $query->with(['dealStage:id,name,color', 'assignedToUser:id,first_name,last_name', 'contact:id,first_name,last_name'])
                    ->orderBy('expected_close_date', 'desc');
            },
            'tasks' => function ($query) use ($completedTaskStatusIds) { // Eager load tasks related to this company
                $query->whereNotIn('task_status_id', $completedTaskStatusIds) // Show open tasks by default
                    ->with(['status:id,name,color', 'priority:id,name,color', 'assignedToUser:id,first_name,last_name', 'taskable'])
                    ->orderByRaw('ISNULL(due_date) ASC, due_date ASC'); // Order by due date, nulls last
            },
            'assignedToUser:id,first_name,last_name', // From existing model
        ]);

        // For OwenIt\Auditing package, if Company model uses the Auditable trait
        $activities = $company->audits()->with('user:id,first_name,last_name')->latest()->limit(20)->get();

        return view('crmcore::companies.show', compact('company', 'activities'));
    }

    /**
     * Show the form for editing the specified company.
     */
    public function edit(Company $company) // Route model binding
    {
        return view('crmcore::companies.edit', compact('company'));
    }

    /**
     * Update the specified company in storage.
     */
    public function update(Request $request, Company $company) // Route model binding
    {
        // $this->authorize('update', $company);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone_office' => 'nullable|string|max:50',
            'email_office' => 'nullable|email|max:255|unique:companies,email_office,'.$company->id.(isset($request->tenant_id) ? ',id,tenant_id,'.$request->tenant_id : ''),
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'assigned_to_user_id' => 'nullable|exists:users,id',
            // 'tenant_id' should not typically be updated once set.
        ]);

        if ($validator->fails()) {
            return redirect()->route('companies.edit', $company->id)
                ->withErrors($validator)
                ->withInput();
        }

        try {
            DB::beginTransaction();
            $data = $validator->validated();
            $data['is_active'] = $request->boolean('is_active');
            // UserActionsTrait handles updated_by_id

            $company->update($data);
            DB::commit();

            return redirect()->route('companies.index')->with('success', 'Company updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Company Update Error for ID {$company->id}: ".$e->getMessage());

            return redirect()->route('companies.edit', $company->id)
                ->with('error', 'Failed to update company: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified company from storage (soft delete).
     */
    public function destroy(Company $company)
    {
        // $this->authorize('delete', $company);
        try {
            // Check for related records
            if ($company->contacts()->count() > 0) {
                return Error::response(__('Cannot delete company with existing contacts'));
            }

            $company->delete(); // SoftDeletes trait handles this

            return Success::response(['message' => __('Company deleted successfully')]);
        } catch (Exception $e) {
            Log::error("Company Delete Error for ID {$company->id}: ".$e->getMessage());

            return Error::response(__('Failed to delete company'));
        }
    }

    /**
     * Toggle the active status of a company.
     */
    public function toggleStatus(Request $request, Company $company)
    {
        // $this->authorize('update', $company);
        try {
            $company->is_active = ! $company->is_active;
            $company->save();

            // UserActionsTrait should handle updated_by_id
            return Success::response([
                'message' => __('Status updated successfully'),
                'is_active' => $company->is_active,
            ]);
        } catch (Exception $e) {
            Log::error("Company Toggle Status Error for ID {$company->id}: ".$e->getMessage());

            return Error::response(__('Failed to update status'));
        }
    }

    /**
     * Get updated company data for AJAX refresh after deal creation.
     */
    public function getCompanyDealsAjax(Company $company)
    {
        try {
            $company->load([
                'deals' => function ($query) {
                    $query->with(['dealStage:id,name,color', 'assignedToUser:id,first_name,last_name', 'contact:id,first_name,last_name'])
                        ->orderBy('expected_close_date', 'desc');
                },
            ]);

            return Success::response([
                'deals' => $company->deals,
            ]);
        } catch (Exception $e) {
            Log::error("Company Get Deals Ajax Error for ID {$company->id}: ".$e->getMessage());

            return Error::response(__('Failed to fetch updated deals data'));
        }
    }

    /**
     * Search for companies for Select2 AJAX.
     */
    public function selectSearch(Request $request)
    {
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $resultsPerPage = 15;

        $query = Company::where('is_active', true)
            ->where('name', 'like', "%{$searchTerm}%")
            ->orderBy('name');

        $companies = $query->paginate($resultsPerPage, ['id', 'name'], 'page', $page);

        $formattedCompanies = $companies->map(function ($company) {
            return ['id' => $company->id, 'text' => $company->name];
        });

        return response()->json([
            'results' => $formattedCompanies,
            'pagination' => ['more' => $companies->hasMorePages()],
        ]);
    }
}
