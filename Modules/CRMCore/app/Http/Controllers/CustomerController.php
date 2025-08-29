<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Customer;
use Modules\CRMCore\app\Models\CustomerGroup;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $customerGroups = CustomerGroup::active()->get();

        return view('crmcore::customers.index', compact('customerGroups'));
    }

    /**
     * Get data for DataTables
     */
    public function datatable(Request $request)
    {
        $query = Customer::with(['contact', 'customerGroup'])
            ->select('customers.*');

        // Apply filters

        if ($request->filled('customer_group_id')) {
            $query->where('customer_group_id', $request->customer_group_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return DataTables::of($query)
            ->addColumn('customer_name', function ($customer) {
                // Use the contact directly but add the code property
                $customer->contact->code = $customer->code;

                return view('components.datatable-user', [
                    'user' => $customer->contact,
                    'showCode' => true,
                    'linkRoute' => 'customers.show',
                    'avatarSize' => 'sm',
                ])->render();
            })
            ->addColumn('contact_info', function ($customer) {
                $html = '';
                if ($customer->email) {
                    $html .= '<div><i class="bx bx-envelope text-muted me-1"></i>'.e($customer->email).'</div>';
                }
                if ($customer->phone) {
                    $html .= '<div><i class="bx bx-phone text-muted me-1"></i>'.e($customer->phone).'</div>';
                }

                return $html ?: '<span class="text-muted">-</span>';
            })
            ->addColumn('customer_group_badge', function ($customer) {
                if ($customer->customerGroup) {
                    return '<span class="badge bg-info">'.$customer->customerGroup->name.'</span>';
                }

                return '<span class="badge bg-secondary">'.__('No Group').'</span>';
            })
            ->addColumn('lifetime_value_formatted', function ($customer) {
                return '<span class="fw-semibold">'.number_format($customer->lifetime_value, 2).'</span>';
            })
            ->addColumn('status', function ($customer) {
                if ($customer->is_blacklisted) {
                    return '<span class="badge bg-danger">'.__('Blacklisted').'</span>';
                }

                return $customer->is_active
                    ? '<span class="badge bg-success">'.__('Active').'</span>'
                    : '<span class="badge bg-secondary">'.__('Inactive').'</span>';
            })
            ->addColumn('actions', function ($customer) {
                return view('components.datatable-actions', [
                    'id' => $customer->id,
                    'actions' => [
                        [
                            'label' => __('View'),
                            'icon' => 'bx bx-show',
                            'onclick' => "viewCustomer({$customer->id})",
                        ],
                        [
                            'label' => __('Edit'),
                            'icon' => 'bx bx-edit',
                            'onclick' => "editCustomer({$customer->id})",
                        ],
                        $customer->is_blacklisted ? [
                            'label' => __('Remove from Blacklist'),
                            'icon' => 'bx bx-check-circle',
                            'onclick' => "toggleBlacklist({$customer->id}, false)",
                            'class' => 'text-success',
                        ] : [
                            'label' => __('Add to Blacklist'),
                            'icon' => 'bx bx-block',
                            'onclick' => "toggleBlacklist({$customer->id}, true)",
                            'class' => 'text-danger',
                        ],
                    ],
                ])->render();
            })
            ->rawColumns(['customer_name', 'contact_info', 'customer_group_badge', 'lifetime_value_formatted', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customerGroups = CustomerGroup::active()->get();
        $contacts = Contact::whereDoesntHave('customer')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('crmcore::customers.create', compact('customerGroups', 'contacts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'contact_id' => 'required|exists:contacts,id|unique:customers,contact_id',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'required|in:cod,net30,net60,prepaid',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_exempt' => 'boolean',
            'tax_number' => 'nullable|string|max:255',
            'business_registration' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->all();
            // Code will be auto-generated by HasCRMCode trait
            $data['is_active'] = true;
            $data['first_purchase_date'] = now();
            $data['tax_exempt'] = $request->has('tax_exempt') ? 1 : 0;

            $customer = Customer::create($data);

            DB::commit();

            return Success::response([
                'message' => __('Customer created successfully'),
                'redirect' => route('customers.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Customer creation failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return Error::response('Failed to create customer: '.$e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $customer = Customer::with(['contact', 'customerGroup'])->findOrFail($id);

        // Get recent transactions (placeholder - will be implemented when sales module is ready)
        $recentTransactions = [];

        return view('crmcore::customers.show', compact('customer', 'recentTransactions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        $customerGroups = CustomerGroup::active()->get();

        return view('crmcore::customers.edit', compact('customer', 'customerGroups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'required|in:cod,net30,net60,prepaid',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_exempt' => 'boolean',
            'tax_number' => 'nullable|string|max:255',
            'business_registration' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $customer = Customer::findOrFail($id);

            $data = $request->all();
            $data['tax_exempt'] = $request->has('tax_exempt') ? 1 : 0;
            $data['is_active'] = $request->has('is_active') ? 1 : 0;

            $customer->update($data);

            DB::commit();

            return Success::response([
                'message' => __('Customer updated successfully'),
                'redirect' => route('customers.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return Error::response(__('Failed to update customer'));
        }
    }

    /**
     * Toggle blacklist status
     */
    public function toggleBlacklist(Request $request, $id)
    {
        $request->validate([
            'blacklist' => 'required|boolean',
            'reason' => 'required_if:blacklist,true|nullable|string',
        ]);

        try {
            $customer = Customer::findOrFail($id);

            $customer->update([
                'is_blacklisted' => $request->blacklist,
                'blacklist_reason' => $request->blacklist ? $request->reason : null,
            ]);

            $message = $request->blacklist
                ? __('Customer has been blacklisted')
                : __('Customer has been removed from blacklist');

            return Success::response(['message' => $message]);
        } catch (\Exception $e) {
            return Error::response(__('Failed to update blacklist status'));
        }
    }

    /**
     * Get customer statistics
     */
    public function statistics()
    {
        try {
            $stats = [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::active()->count(),
                'new_customers_this_month' => Customer::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'customers_with_groups' => Customer::whereNotNull('customer_group_id')->count(),
                'total_lifetime_value' => Customer::sum('lifetime_value'),
                'blacklisted_customers' => Customer::where('is_blacklisted', true)->count(),
            ];

            return Success::response($stats);
        } catch (\Exception $e) {
            return Error::response(__('Failed to load statistics'));
        }
    }

    /**
     * Search contacts for customer creation
     */
    public function searchContacts(Request $request)
    {
        $search = $request->get('q', '');

        $contacts = Contact::whereDoesntHave('customer')
            ->where('is_active', true)
            ->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email_primary', 'like', "%{$search}%")
                    ->orWhere('phone_primary', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($contact) {
                return [
                    'id' => $contact->id,
                    'text' => $contact->getFullNameAttribute().' ('.($contact->email_primary ?: $contact->phone_primary ?: 'No contact').')',
                ];
            });

        return response()->json(['results' => $contacts]);
    }
}
