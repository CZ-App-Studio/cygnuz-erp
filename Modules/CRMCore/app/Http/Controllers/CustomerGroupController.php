<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\ApiClasses\Success;
use App\ApiClasses\Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRMCore\app\Models\CustomerGroup;
use Yajra\DataTables\DataTables;

class CustomerGroupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('crmcore::customer-groups.index');
    }

    /**
     * Get data for DataTables
     */
    public function datatable(Request $request)
    {
        $query = CustomerGroup::select('customer_groups.*')
            ->withCount('customers');

        // Apply filters
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        return DataTables::of($query)
            ->addColumn('name_with_code', function ($group) {
                return '<div class="d-flex flex-column">' .
                    '<span class="fw-medium">' . e($group->name) . '</span>' .
                    '<small class="text-muted">' . e($group->code) . '</small>' .
                    '</div>';
            })
            ->addColumn('discount_info', function ($group) {
                if ($group->discount_percentage > 0) {
                    return '<span class="badge bg-success">' . $group->discount_percentage . '%</span>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('priority_badge', function ($group) {
                return '<span class="badge bg-primary">' . $group->priority . '</span>';
            })
            ->addColumn('customers_count_badge', function ($group) {
                $color = $group->customers_count > 0 ? 'info' : 'secondary';
                return '<span class="badge bg-' . $color . '">' . $group->customers_count . ' ' . __('Customers') . '</span>';
            })
            ->addColumn('status', function ($group) {
                return $group->is_active 
                    ? '<span class="badge bg-success">' . __('Active') . '</span>'
                    : '<span class="badge bg-secondary">' . __('Inactive') . '</span>';
            })
            ->addColumn('actions', function ($group) {
                $actions = [
                    [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editCustomerGroup({$group->id})"
                    ]
                ];

                // Don't allow deletion if group has customers
                if ($group->customers_count == 0) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteCustomerGroup({$group->id})",
                        'class' => 'text-danger'
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $group->id,
                    'actions' => $actions
                ])->render();
            })
            ->rawColumns(['name_with_code', 'discount_info', 'priority_badge', 'customers_count_badge', 'status', 'actions'])
            ->make(true);
    }

    /**
     * Get statistics for customer groups
     */
    public function statistics()
    {
        $stats = [
            'total_groups' => CustomerGroup::count(),
            'active_groups' => CustomerGroup::where('is_active', true)->count(),
            'groups_with_discounts' => CustomerGroup::where('discount_percentage', '>', 0)->count(),
            'total_customers_in_groups' => CustomerGroup::withCount('customers')->get()->sum('customers_count'),
        ];

        return Success::response($stats);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:customer_groups,code',
            'description' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'priority' => 'required|integer|min:0',
            'is_active' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['is_active'] = $request->input('is_active', 0);
            
            $customerGroup = CustomerGroup::create($data);

            DB::commit();
            return Success::response([
                'message' => __('Customer group created successfully'),
                'customer_group' => $customerGroup
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create customer group: ' . $e->getMessage());
            return Error::response(__('Failed to create customer group') . ': ' . $e->getMessage());
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $customerGroup = CustomerGroup::findOrFail($id);
        return Success::response($customerGroup);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $customerGroup = CustomerGroup::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:customer_groups,code,' . $id,
            'description' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'priority' => 'required|integer|min:0',
            'is_active' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $data = $request->all();
            $data['is_active'] = $request->input('is_active', 0);
            
            $customerGroup->update($data);

            DB::commit();
            return Success::response([
                'message' => __('Customer group updated successfully'),
                'customer_group' => $customerGroup
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return Error::response(__('Failed to update customer group'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $customerGroup = CustomerGroup::withCount('customers')->findOrFail($id);

        if ($customerGroup->customers_count > 0) {
            return Error::response(__('Cannot delete customer group with existing customers'));
        }

        DB::beginTransaction();
        try {
            $customerGroup->delete();
            DB::commit();
            return Success::response([
                'message' => __('Customer group deleted successfully')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return Error::response(__('Failed to delete customer group'));
        }
    }
}