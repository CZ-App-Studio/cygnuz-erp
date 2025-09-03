<?php

namespace Modules\CRMCore\app\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Http\Controllers\Api\BaseApiController;
use Modules\CRMCore\app\Http\Resources\ContactResource;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;

class ContactController extends BaseApiController
{
    /**
     * Display a listing of contacts
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Contact::with(['company', 'createdBy']);

            // Filter by company
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by primary contact
            if ($request->has('is_primary')) {
                $query->where('is_primary', $request->boolean('is_primary'));
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
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

            $contacts = $query->paginate($request->input('per_page', 20));

            return $this->successResponse(
                ContactResource::collection($contacts),
                'Contacts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contacts', $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created contact
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:255|unique:contacts,email',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:companies,id',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $data = $request->all();
            $data['created_by_id'] = Auth::id();
            $data['tenant_id'] = $request->header('X-Tenant-Id');

            // If setting as primary, unset other primary contacts for the company
            if (isset($data['is_primary']) && $data['is_primary'] && isset($data['company_id'])) {
                Contact::where('company_id', $data['company_id'])
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $contact = Contact::create($data);
            $contact->load(['company', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                new ContactResource($contact),
                'Contact created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to create contact', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified contact
     */
    public function show($id): JsonResponse
    {
        try {
            $contact = Contact::with(['company', 'createdBy'])->findOrFail($id);

            return $this->successResponse(
                new ContactResource($contact),
                'Contact retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Contact not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contact', $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified contact
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'nullable|email|max:255|unique:contacts,email,'.$id,
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'title' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'company_id' => 'nullable|exists:companies,id',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|string|max:100',
            'facebook' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'is_primary' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            DB::beginTransaction();

            $contact = Contact::findOrFail($id);

            $data = $request->all();
            $data['updated_by_id'] = Auth::id();

            // If setting as primary, unset other primary contacts for the company
            if (isset($data['is_primary']) && $data['is_primary'] && $contact->company_id) {
                Contact::where('company_id', $contact->company_id)
                    ->where('id', '!=', $id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            $contact->update($data);
            $contact->load(['company', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                new ContactResource($contact),
                'Contact updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return $this->notFoundResponse('Contact not found');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to update contact', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified contact
     */
    public function destroy($id): JsonResponse
    {
        try {
            $contact = Contact::findOrFail($id);

            // Check if contact is primary
            if ($contact->is_primary) {
                return $this->errorResponse('Cannot delete primary contact', null, 400);
            }

            $contact->delete();

            return $this->successResponse(null, 'Contact deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Contact not found');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete contact', $e->getMessage(), 500);
        }
    }

    /**
     * Search contacts
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $search = $request->input('search', '');

            $contacts = Contact::with(['company'])
                ->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                })
                ->limit(10)
                ->get();

            return $this->successResponse(
                ContactResource::collection($contacts),
                'Contacts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search contacts', $e->getMessage(), 500);
        }
    }

    /**
     * Set contact as primary
     */
    public function setPrimary($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $contact = Contact::findOrFail($id);

            if (! $contact->company_id) {
                return $this->errorResponse('Contact must belong to a company to be set as primary', null, 400);
            }

            // Unset other primary contacts for the company
            Contact::where('company_id', $contact->company_id)
                ->where('id', '!=', $id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            // Set this contact as primary
            $contact->is_primary = true;
            $contact->save();

            $contact->load(['company', 'createdBy']);

            DB::commit();

            return $this->successResponse(
                new ContactResource($contact),
                'Contact set as primary successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return $this->notFoundResponse('Contact not found');
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to set contact as primary', $e->getMessage(), 500);
        }
    }
}
