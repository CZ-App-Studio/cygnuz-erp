<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\ApiClasses\Success;
use App\ApiClasses\Error;
use App\Helpers\FormattingHelper;
use Modules\WMSInventoryCore\Models\Vendor;
use Modules\WMSInventoryCore\Models\Purchase;

class VendorController extends Controller
{
    /**
     * Display a listing of the vendors.
     *
     * @return Renderable
     */
    public function index()
    {
        // $this->authorize('wmsinventory.view-vendors');
        
        return view('wmsinventorycore::vendors.index');
    }

    /**
     * Process ajax request for vendors datatable.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        // $this->authorize('wmsinventory.view-vendors');
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowPerPage = $request->get("length");

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $searchValue = $request->get('search')['value'] ?? '';
        $statusFilter = $request->get('status') ?? '';

        // Query builder with filters
        $query = Vendor::withCount(['purchases'])
            ->withSum('purchases', 'total_amount')
            ->with(['createdBy', 'updatedBy']);

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('email', 'like', '%' . $searchValue . '%')
                    ->orWhere('company_name', 'like', '%' . $searchValue . '%')
                    ->orWhere('phone_number', 'like', '%' . $searchValue . '%');
            });
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        // Get total count
        $totalRecords = $query->count();

        // Handle ordering
        if (!empty($columnIndex_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'];
            $columnName = $columnName_arr[$columnIndex]['data'];
            $columnSortOrder = $order_arr[0]['dir'];

            if ($columnName != 'actions') {
                $query->orderBy($columnName, $columnSortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        // Apply pagination
        $vendors = $query->skip($start)
            ->take($rowPerPage)
            ->get();

        // Format data for DataTables
        $data = [];

        foreach ($vendors as $vendor) {
            // Calculate outstanding balance
            $outstandingBalance = $vendor->purchases->sum(function($purchase) {
                return ($purchase->total_amount ?? 0) - ($purchase->paid_amount ?? 0);
            });

            $data[] = [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'email' => $vendor->email ?? '-',
                'phone_number' => $vendor->phone_number ?? '-',
                'company_name' => $vendor->company_name ?? '-',
                'status' => view('components.status-badge', [
                    'status' => $vendor->status,
                    'type' => $vendor->status === 'active' ? 'success' : 'secondary'
                ])->render(),
                'payment_terms' => $vendor->payment_terms ?? '-',
                'lead_time_days' => $vendor->lead_time_days ? $vendor->lead_time_days . ' ' . __('days') : '-',
                'actions' => view('components.datatable-actions', [
                    'id' => $vendor->id,
                    'actions' => [
                        ['label' => __('View'), 'icon' => 'bx bx-show', 'onclick' => "viewVendor({$vendor->id})"],
                        ['label' => __('Edit'), 'icon' => 'bx bx-edit', 'onclick' => "editVendor({$vendor->id})"],
                        ['label' => __('Delete'), 'icon' => 'bx bx-trash', 'onclick' => "deleteVendor({$vendor->id})"],
                    ]
                ])->render()
            ];
        }

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
            "data" => $data
        ]);
    }

    /**
     * Show the form for creating a new vendor.
     *
     * @return Renderable
     */
    public function create()
    {
        // $this->authorize('wmsinventory.create-vendor');
        
        return view('wmsinventorycore::vendors.create');
    }

    /**
     * Store a newly created vendor in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // $this->authorize('wmsinventory.create-vendor');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:vendors,email',
            'phone_number' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'website' => 'nullable|url',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'lead_time_days' => 'nullable|integer|min:0',
            'minimum_order_value' => 'nullable|numeric|min:0',
        ]);

        try {
            $vendor = null;
            DB::transaction(function () use ($validated, &$vendor) {
                $vendor = Vendor::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'] ?? null,
                    'company_name' => $validated['company_name'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'state' => $validated['state'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'tax_number' => $validated['tax_number'] ?? null,
                    'website' => $validated['website'] ?? null,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? null,
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'lead_time_days' => $validated['lead_time_days'] ?? null,
                    'minimum_order_value' => $validated['minimum_order_value'] ?? null,
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            // Check if it's an AJAX request
            if ($request->ajax()) {
                return Success::response([
                    'message' => __('Vendor has been created successfully'),
                    'vendor' => $vendor
                ]);
            }

            return redirect()->route('wmsinventorycore.vendors.show', $vendor->id)
                ->with('success', __('Vendor has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create vendor: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', __('Failed to create vendor'))
                ->withInput();
        }
    }

    /**
     * Display the specified vendor.
     *
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        // $this->authorize('wmsinventory.view-vendors');
        
        $vendor = Vendor::with(['createdBy', 'updatedBy'])->findOrFail($id);

        // Get vendor statistics
        $totalPurchases = $vendor->purchases->sum('total_amount') ?? 0;
        $totalPaid = $vendor->purchases->sum('paid_amount') ?? 0;
        $outstandingBalance = $totalPurchases - $totalPaid;
        $purchaseCount = $vendor->purchases->count();

        // Get recent purchase history
        $recentPurchases = Purchase::where('vendor_id', $id)
            ->with(['warehouse'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get monthly purchase statistics for chart
        $monthlyPurchases = Purchase::where('vendor_id', $id)
            ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(total_amount) as total')
            ->groupByRaw('YEAR(created_at), MONTH(created_at)')
            ->orderByRaw('YEAR(created_at) DESC, MONTH(created_at) DESC')
            ->limit(12)
            ->get();

        return view('wmsinventorycore::vendors.show', [
            'vendor' => $vendor,
            'totalPurchases' => FormattingHelper::formatCurrency($totalPurchases),
            'totalPaid' => FormattingHelper::formatCurrency($totalPaid),
            'outstandingBalance' => FormattingHelper::formatCurrency($outstandingBalance),
            'purchaseCount' => $purchaseCount,
            'recentPurchases' => $recentPurchases,
            'monthlyPurchases' => $monthlyPurchases
        ]);
    }

    /**
     * Show the form for editing the specified vendor.
     *
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        // $this->authorize('wmsinventory.edit-vendor');
        
        $vendor = Vendor::findOrFail($id);

        // Check if it's an AJAX request for offcanvas form
        if (request()->ajax()) {
            return Success::response([
                'vendor' => $vendor
            ]);
        }

        return view('wmsinventorycore::vendors.edit', [
            'vendor' => $vendor
        ]);
    }

    /**
     * Update the specified vendor in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // $this->authorize('wmsinventory.edit-vendor');
        
        $vendor = Vendor::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:vendors,email,' . $vendor->id,
            'phone_number' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'website' => 'nullable|url',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string|max:100',
            'lead_time_days' => 'nullable|integer|min:0',
            'minimum_order_value' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($vendor, $validated) {
                $vendor->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone_number' => $validated['phone_number'] ?? $vendor->phone_number,
                    'company_name' => $validated['company_name'] ?? $vendor->company_name,
                    'address' => $validated['address'] ?? $vendor->address,
                    'city' => $validated['city'] ?? $vendor->city,
                    'state' => $validated['state'] ?? $vendor->state,
                    'country' => $validated['country'] ?? $vendor->country,
                    'postal_code' => $validated['postal_code'] ?? $vendor->postal_code,
                    'tax_number' => $validated['tax_number'] ?? $vendor->tax_number,
                    'website' => $validated['website'] ?? $vendor->website,
                    'status' => $validated['status'],
                    'notes' => $validated['notes'] ?? $vendor->notes,
                    'payment_terms' => $validated['payment_terms'] ?? $vendor->payment_terms,
                    'lead_time_days' => $validated['lead_time_days'] ?? $vendor->lead_time_days,
                    'minimum_order_value' => $validated['minimum_order_value'] ?? $vendor->minimum_order_value,
                    'updated_by_id' => auth()->id(),
                ]);
            });

            // Check if it's an AJAX request
            if ($request->ajax()) {
                return Success::response([
                    'message' => __('Vendor has been updated successfully'),
                    'vendor' => $vendor
                ]);
            }

            return redirect()->route('wmsinventorycore.vendors.show', $vendor->id)
                ->with('success', __('Vendor has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update vendor: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', __('Failed to update vendor'))
                ->withInput();
        }
    }

    /**
     * Remove the specified vendor from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // $this->authorize('wmsinventory.delete-vendor');
        
        try {
            $vendor = Vendor::findOrFail($id);

            // Check if vendor has associated purchases
            if ($vendor->purchases()->exists()) {
                return Error::response(__('Cannot delete vendor. Vendor has associated purchase records.'));
            }

            DB::transaction(function () use ($vendor) {
                $vendor->delete();
            });

            return Success::response(__('Vendor has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete vendor: ' . $e->getMessage());
            return Error::response(__('Failed to delete vendor'));
        }
    }

    /**
     * Global vendor search for Select2 integration
     * Used by other modules (Purchasing, etc.)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchVendors(Request $request)
    {
        // $this->authorize('wmsinventory.search-vendors');
        
        try {
            $search = $request->get('search', '');
            $activeOnly = $request->get('active_only', true);
            $limit = $request->get('limit', 50);

            $query = Vendor::query();

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($activeOnly) {
                $query->where('status', 'active');
            }

            $vendors = $query->limit($limit)->get()->map(function ($vendor) {
                return [
                    'id' => $vendor->id,
                    'text' => $vendor->company_name ? "{$vendor->name} ({$vendor->company_name})" : $vendor->name,
                    'name' => $vendor->name,
                    'company_name' => $vendor->company_name,
                    'email' => $vendor->email,
                    'phone_number' => $vendor->phone_number,
                    'payment_terms' => $vendor->payment_terms,
                    'lead_time_days' => $vendor->lead_time_days,
                    'minimum_order_value' => $vendor->minimum_order_value,
                    'status' => $vendor->status,
                    'full_address' => $vendor->full_address,
                ];
            });

            return response()->json($vendors);
        } catch (\Exception $e) {
            Log::error('Global vendor search failed: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}