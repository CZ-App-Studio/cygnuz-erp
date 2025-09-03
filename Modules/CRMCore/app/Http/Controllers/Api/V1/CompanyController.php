<?php

namespace Modules\CRMCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Http\Controllers\Api\BaseApiController;
use Modules\CRMCore\app\Http\Resources\CompanyResource;
use Modules\CRMCore\app\Http\Resources\ContactResource;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;

class CompanyController extends BaseApiController
{
    /**
     * Display a listing of companies
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Company::with(['contacts', 'primaryContact', 'createdBy']);

            // Filter by industry
            if ($request->has('industry')) {
                $query->where('industry', $request->industry);
            }

            // Filter by employee size
            // Employee filters removed - column doesn't exist in database

            // Revenue filters removed - column doesn't exist in database

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('website', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
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

            $companies = $query->paginate($request->input('per_page', 20));

            return $this->successResponse(
                CompanyResource::collection($companies),
                'Companies retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve companies', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created company
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:companies,name',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'logo' => 'nullable|string|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:100',
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

            $company = Company::create($data);
            $company->load(['contacts', 'primaryContact', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                new CompanyResource($company),
                'Company created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to create company', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified company
     */
    public function show($id): JsonResponse
    {
        try {
            $company = Company::with(['contacts', 'primaryContact', 'createdBy'])
                ->findOrFail($id);

            return $this->successResponse(
                new CompanyResource($company),
                'Company retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Company not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve company', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified company
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:companies,name,'.$id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'industry' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'logo' => 'nullable|string|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $company = Company::findOrFail($id);

            $data = $request->all();
            $data['updated_by_id'] = Auth::id();

            $company->update($data);
            $company->load(['contacts', 'primaryContact', 'createdBy']);

            return $this->successResponse(
                new CompanyResource($company),
                'Company updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Company not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update company', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified company
     */
    public function destroy($id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);

            // Check if company has related records
            if ($company->contacts()->exists()) {
                return $this->errorResponse('Cannot delete company with existing contacts', null, 400);
            }

            if ($company->leads()->exists()) {
                return $this->errorResponse('Cannot delete company with existing leads', null, 400);
            }

            if ($company->deals()->exists()) {
                return $this->errorResponse('Cannot delete company with existing deals', null, 400);
            }

            $company->delete();

            return $this->successResponse(null, 'Company deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Company not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete company', $e->getMessage(), 500);
        }
    }

    /**
     * Search companies
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search', '');

            $companies = Company::with(['primaryContact'])
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('website', 'like', "%{$search}%");
                })
                ->limit(10)
                ->get();

            return $this->successResponse(
                CompanyResource::collection($companies),
                'Companies retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search companies', $e->getMessage(), 500);
        }
    }

    /**
     * Get company contacts
     */
    public function getContacts($id): JsonResponse
    {
        try {
            $company = Company::findOrFail($id);
            $contacts = $company->contacts()->with(['createdBy'])->paginate(20);

            return $this->successResponse(
                ContactResource::collection($contacts),
                'Company contacts retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Company not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve company contacts', $e->getMessage(), 500);
        }
    }

    /**
     * Add contact to company
     */
    public function addContact(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:contacts,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $company = Company::findOrFail($id);

            $data = $request->all();
            $data['company_id'] = $company->id;
            $data['created_by_id'] = Auth::id();
            $data['tenant_id'] = $request->header('X-Tenant-Id');

            // If setting as primary, unset other primary contacts
            if (isset($data['is_primary']) && $data['is_primary']) {
                Contact::where('company_id', $company->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $contact = Contact::create($data);
            $contact->load(['company', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                new ContactResource($contact),
                'Contact added to company successfully',
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return $this->notFoundResponse('Company not found');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to add contact to company', $e->getMessage(), 500);
        }
    }

    /**
     * Get company statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $query = Company::query();

            // Apply date filters
            if ($request->has('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $stats = [
                'total_companies' => $query->count(),
                'companies_by_industry' => (clone $query)->select('industry', DB::raw('count(*) as count'))
                    ->whereNotNull('industry')
                    ->groupBy('industry')
                    ->pluck('count', 'industry'),
                'companies_with_contacts' => (clone $query)->has('contacts')->count(),
                'companies_with_deals' => (clone $query)->has('deals')->count(),
                'recent_companies' => (clone $query)->latest()->take(5)->get()->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'industry' => $company->industry,
                        'created_at' => $company->created_at,
                    ];
                }),
            ];

            return $this->successResponse($stats, 'Company statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve statistics', $e->getMessage(), 500);
        }
    }
}
