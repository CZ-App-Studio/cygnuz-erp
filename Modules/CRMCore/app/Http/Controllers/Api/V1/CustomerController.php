<?php

namespace Modules\CRMCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Http\Controllers\Api\BaseApiController;
use Modules\CRMCore\app\Http\Resources\CustomerResource;
use Modules\CRMCore\app\Models\Customer;
use Modules\CRMCore\app\Models\CustomerGroup;

class CustomerController extends BaseApiController
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Customer::with(['customerGroup', 'contact.company', 'contact.assignedToUser', 'createdBy']);

            // Filter by customer type (based on group or default)
            if ($request->has('customer_type')) {
                // Filter through customer group if needed
                // For now, using the customer_type accessor from model
            }

            // Filter by customer group
            if ($request->has('customer_group_id')) {
                $query->where('customer_group_id', $request->customer_group_id);
            }

            // Filter by status
            if ($request->has('status')) {
                switch($request->status) {
                    case 'active':
                        $query->where('is_active', true)->where('is_blacklisted', false);
                        break;
                    case 'inactive':
                        $query->where('is_active', false);
                        break;
                    case 'blocked':
                        $query->where('is_blacklisted', true);
                        break;
                }
            }

            // Filter by assigned user (through contact)
            if ($request->has('assigned_to')) {
                $query->whereHas('contact', function($q) use ($request) {
                    $q->where('assigned_to_user_id', $request->assigned_to);
                });
            }

            // Filter by credit limit
            if ($request->has('min_credit_limit')) {
                $query->where('credit_limit', '>=', $request->min_credit_limit);
            }
            if ($request->has('max_credit_limit')) {
                $query->where('credit_limit', '<=', $request->max_credit_limit);
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('tax_number', 'like', "%{$search}%")
                        ->orWhereHas('contact', function($contactQuery) use ($search) {
                            $contactQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email_primary', 'like', "%{$search}%")
                                ->orWhere('phone_primary', 'like', "%{$search}%")
                                ->orWhere('phone_mobile', 'like', "%{$search}%");
                        })
                        ->orWhereHas('contact.company', function($companyQuery) use ($search) {
                            $companyQuery->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $customers = $query->paginate($request->input('per_page', 20));

            return $this->successResponse(
                CustomerResource::collection($customers),
                'Customers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customers', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Contact information (required if no contact_id)
            'contact_id' => 'nullable|exists:contacts,id',
            'first_name' => 'required_without:contact_id|string|max:100',
            'last_name' => 'required_without:contact_id|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'company_id' => 'nullable|exists:companies,id',
            'job_title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            
            // Address information
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_country' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            
            // Customer specific information
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|in:net30,net60,cod,prepaid',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_number' => 'nullable|string|max:50',
            'tax_exempt' => 'nullable|boolean',
            'business_registration' => 'nullable|string|max:100',
            'preferred_payment_method' => 'nullable|string|max:50',
            'preferred_delivery_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $tenantId = $request->header('X-Tenant-Id');
            $contactId = $request->contact_id;
            
            // If no contact_id provided, create a new contact first
            if (!$contactId) {
                $contactData = [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email_primary' => $request->email,
                    'phone_primary' => $request->phone,
                    'phone_mobile' => $request->mobile,
                    'company_id' => $request->company_id,
                    'job_title' => $request->job_title,
                    'department' => $request->department,
                    'address_street' => $request->address_street,
                    'address_city' => $request->address_city,
                    'address_state' => $request->address_state,
                    'address_country' => $request->address_country,
                    'address_postal_code' => $request->address_postal_code,
                    'assigned_to_user_id' => $request->assigned_to,
                    'is_active' => true,
                    'tenant_id' => $tenantId,
                    'created_by_id' => Auth::id(),
                ];
                
                $contact = Contact::create($contactData);
                $contactId = $contact->id;
            }
            
            // Check if customer already exists for this contact
            $existingCustomer = Customer::where('contact_id', $contactId)->first();
            if ($existingCustomer) {
                return $this->errorResponse('A customer already exists for this contact', null, 400);
            }
            
            // Create customer record
            $customerData = [
                'contact_id' => $contactId,
                'customer_group_id' => $request->customer_group_id,
                'credit_limit' => $request->credit_limit ?? 0,
                'payment_terms' => $request->payment_terms ?? 'cod',
                'discount_percentage' => $request->discount_percentage ?? 0,
                'tax_number' => $request->tax_number,
                'tax_exempt' => $request->tax_exempt ?? false,
                'business_registration' => $request->business_registration,
                'preferred_payment_method' => $request->preferred_payment_method,
                'preferred_delivery_method' => $request->preferred_delivery_method,
                'notes' => $request->notes,
                'tags' => $request->tags,
                'is_active' => true,
                'is_blacklisted' => false,
                'lifetime_value' => 0,
                'purchase_count' => 0,
                'average_order_value' => 0,
                'credit_used' => 0,
                'tenant_id' => $tenantId,
                'created_by_id' => Auth::id(),
            ];

            $customer = Customer::create($customerData);
            $customer->load(['customerGroup', 'contact.company', 'contact.assignedToUser', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create customer', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified customer
     */
    public function show($id): JsonResponse
    {
        try {
            $customer = Customer::with(['customerGroup', 'contact.company', 'contact.assignedToUser', 'createdBy'])
                ->findOrFail($id);

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Customer not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Contact information updates
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'company_id' => 'nullable|exists:companies,id',
            'job_title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            
            // Address information
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_country' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            
            // Customer specific information
            'customer_group_id' => 'nullable|exists:customer_groups,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'nullable|string|in:net30,net60,cod,prepaid',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_number' => 'nullable|string|max:50',
            'tax_exempt' => 'nullable|boolean',
            'business_registration' => 'nullable|string|max:100',
            'preferred_payment_method' => 'nullable|string|max:50',
            'preferred_delivery_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'tags' => 'nullable|array',
            'assigned_to' => 'nullable|exists:users,id',
            'is_active' => 'nullable|boolean',
            'is_blacklisted' => 'nullable|boolean',
            'blacklist_reason' => 'nullable|required_if:is_blacklisted,true|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();
            
            $customer = Customer::findOrFail($id);
            
            // Update contact information if provided
            if ($customer->contact && $request->hasAny(['first_name', 'last_name', 'email', 'phone', 'mobile', 
                'company_id', 'job_title', 'department', 'address_street', 'address_city', 
                'address_state', 'address_country', 'address_postal_code', 'assigned_to'])) {
                
                $contactData = [];
                if ($request->has('first_name')) $contactData['first_name'] = $request->first_name;
                if ($request->has('last_name')) $contactData['last_name'] = $request->last_name;
                if ($request->has('email')) $contactData['email_primary'] = $request->email;
                if ($request->has('phone')) $contactData['phone_primary'] = $request->phone;
                if ($request->has('mobile')) $contactData['phone_mobile'] = $request->mobile;
                if ($request->has('company_id')) $contactData['company_id'] = $request->company_id;
                if ($request->has('job_title')) $contactData['job_title'] = $request->job_title;
                if ($request->has('department')) $contactData['department'] = $request->department;
                if ($request->has('address_street')) $contactData['address_street'] = $request->address_street;
                if ($request->has('address_city')) $contactData['address_city'] = $request->address_city;
                if ($request->has('address_state')) $contactData['address_state'] = $request->address_state;
                if ($request->has('address_country')) $contactData['address_country'] = $request->address_country;
                if ($request->has('address_postal_code')) $contactData['address_postal_code'] = $request->address_postal_code;
                if ($request->has('assigned_to')) $contactData['assigned_to_user_id'] = $request->assigned_to;
                
                if (!empty($contactData)) {
                    $contactData['updated_by_id'] = Auth::id();
                    $customer->contact->update($contactData);
                }
            }
            
            // Update customer specific information
            $customerData = [];
            if ($request->has('customer_group_id')) $customerData['customer_group_id'] = $request->customer_group_id;
            if ($request->has('credit_limit')) $customerData['credit_limit'] = $request->credit_limit;
            if ($request->has('payment_terms')) $customerData['payment_terms'] = $request->payment_terms;
            if ($request->has('discount_percentage')) $customerData['discount_percentage'] = $request->discount_percentage;
            if ($request->has('tax_number')) $customerData['tax_number'] = $request->tax_number;
            if ($request->has('tax_exempt')) $customerData['tax_exempt'] = $request->tax_exempt;
            if ($request->has('business_registration')) $customerData['business_registration'] = $request->business_registration;
            if ($request->has('preferred_payment_method')) $customerData['preferred_payment_method'] = $request->preferred_payment_method;
            if ($request->has('preferred_delivery_method')) $customerData['preferred_delivery_method'] = $request->preferred_delivery_method;
            if ($request->has('notes')) $customerData['notes'] = $request->notes;
            if ($request->has('tags')) $customerData['tags'] = $request->tags;
            if ($request->has('is_active')) $customerData['is_active'] = $request->is_active;
            if ($request->has('is_blacklisted')) $customerData['is_blacklisted'] = $request->is_blacklisted;
            if ($request->has('blacklist_reason')) $customerData['blacklist_reason'] = $request->blacklist_reason;
            
            if (!empty($customerData)) {
                $customerData['updated_by_id'] = Auth::id();
                $customer->update($customerData);
            }
            
            $customer->load(['customerGroup', 'contact.company', 'contact.assignedToUser', 'createdBy']);
            
            DB::commit();

            return $this->successResponse(
                new CustomerResource($customer),
                'Customer updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return $this->notFoundResponse('Customer not found');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update customer', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy($id): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($id);
            
            // Check for related records (invoices, orders, etc.)
            // Add checks as needed based on your business rules
            
            $customer->delete();

            return $this->successResponse(null, 'Customer deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Customer not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete customer', $e->getMessage(), 500);
        }
    }

    /**
     * Search customers
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search', '');
            
            $customers = Customer::with(['customerGroup', 'contact.company'])
                ->where('is_active', true)
                ->where('is_blacklisted', false)
                ->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhereHas('contact', function($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email_primary', 'like', "%{$search}%")
                                ->orWhere('phone_primary', 'like', "%{$search}%");
                        })
                        ->orWhereHas('contact.company', function($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                })
                ->limit(10)
                ->get();

            return $this->successResponse(
                CustomerResource::collection($customers),
                'Customers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search customers', $e->getMessage(), 500);
        }
    }

    /**
     * Get customer groups
     */
    public function getGroups(): JsonResponse
    {
        try {
            $groups = CustomerGroup::where('is_active', true)
                ->orderBy('name')
                ->get();

            return $this->successResponse($groups, 'Customer groups retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve customer groups', $e->getMessage(), 500);
        }
    }

    /**
     * Bulk update customer status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'status' => 'required|in:active,inactive,blocked',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $updateData = ['updated_by_id' => Auth::id(), 'updated_at' => now()];
            
            switch($request->status) {
                case 'active':
                    $updateData['is_active'] = true;
                    $updateData['is_blacklisted'] = false;
                    break;
                case 'inactive':
                    $updateData['is_active'] = false;
                    break;
                case 'blocked':
                    $updateData['is_blacklisted'] = true;
                    break;
            }
            
            Customer::whereIn('id', $request->customer_ids)->update($updateData);

            return $this->successResponse(null, 'Customer statuses updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update customer statuses', $e->getMessage(), 500);
        }
    }

    /**
     * Get customer statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Customer::query();

            // Apply date filters
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $stats = [
                'total_customers' => $query->count(),
                'active_customers' => (clone $query)->where('is_active', true)->where('is_blacklisted', false)->count(),
                'inactive_customers' => (clone $query)->where('is_active', false)->count(),
                'blocked_customers' => (clone $query)->where('is_blacklisted', true)->count(),
                'individual_customers' => (clone $query)->whereHas('contact', function($q) {
                    $q->whereNull('company_id');
                })->count(),
                'company_customers' => (clone $query)->whereHas('contact', function($q) {
                    $q->whereNotNull('company_id');
                })->count(),
                'total_credit_limit' => (clone $query)->sum('credit_limit'),
                'average_credit_limit' => (clone $query)->avg('credit_limit'),
                'customers_by_group' => CustomerGroup::withCount(['customers' => function ($q) use ($query) {
                    $q->whereIn('id', (clone $query)->pluck('id'));
                }])->get()->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                        'count' => $group->customers_count,
                        'discount_percentage' => $group->discount_percentage,
                    ];
                }),
                'recent_customers' => (clone $query)->with('contact')->latest()->take(5)->get()->map(function ($customer) {
                    $contact = $customer->contact;
                    return [
                        'id' => $customer->id,
                        'customer_code' => $customer->code,
                        'name' => $contact ? $contact->first_name . ' ' . $contact->last_name : null,
                        'type' => $customer->customer_type,
                        'created_at' => $customer->created_at,
                    ];
                }),
            ];

            return $this->successResponse($stats, 'Customer statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }

    /**
     * Generate unique customer code
     */
    private function generateCustomerCode(): string
    {
        $prefix = 'CUS';
        $lastCustomer = Customer::orderBy('id', 'desc')->first();
        $number = $lastCustomer ? (intval(substr($lastCustomer->code, 3)) + 1) : 1;
        
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}