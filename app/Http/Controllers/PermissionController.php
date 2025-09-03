<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index()
    {
        if (! auth()->user()->can('view-permissions')) {
            abort(403);
        }

        // Get modules for filter
        $modules = Permission::select('module')
            ->whereNotNull('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('permissions.index', compact('modules'));
    }

    /**
     * Get permissions data for DataTable
     */
    public function indexAjax(Request $request)
    {
        if (! auth()->user()->can('view-permissions')) {
            abort(403);
        }

        $query = Permission::query();

        // Apply module filter
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        // Apply search filter
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('roles', function ($permission) {
                $roles = $permission->roles->pluck('name')->toArray();

                return implode(', ', $roles);
            })
            ->addColumn('actions', function ($permission) {
                $actions = [];

                if (auth()->user()->can('delete-permissions')) {
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deletePermission({$permission->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $permission->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('create-permissions')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'module' => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            DB::beginTransaction();

            $permission = Permission::create([
                'name' => $validated['name'],
                'guard_name' => 'web',
                'module' => $validated['module'],
                'description' => $validated['description'],
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            // Assign to super admin role
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $superAdminRole->givePermissionTo($permission);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'data' => ['message' => __('Permission created successfully')],
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Permission creation failed: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('Failed to create permission'),
            ], 500);
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('delete-permissions')) {
            abort(403);
        }

        try {
            $permission = Permission::findOrFail($id);

            // Check if permission is assigned to any roles
            if ($permission->roles()->count() > 0) {
                return response()->json([
                    'status' => 'failed',
                    'data' => __('Cannot delete permission that is assigned to roles'),
                ], 400);
            }

            $permission->delete();

            return response()->json([
                'status' => 'success',
                'data' => ['message' => __('Permission deleted successfully')],
            ]);

        } catch (Exception $e) {
            Log::error('Permission deletion failed: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('Failed to delete permission'),
            ], 500);
        }
    }

    /**
     * Sync permissions for super admin role
     */
    public function syncSuperAdmin()
    {
        if (! auth()->user()->hasRole('super_admin')) {
            abort(403);
        }

        try {
            $superAdminRole = Role::where('name', 'super_admin')->first();
            if ($superAdminRole) {
                $allPermissions = Permission::all();
                $superAdminRole->syncPermissions($allPermissions);
            }

            return response()->json([
                'status' => 'success',
                'data' => ['message' => __('Super admin permissions synced successfully')],
            ]);

        } catch (Exception $e) {
            Log::error('Permission sync failed: '.$e->getMessage());

            return response()->json([
                'status' => 'failed',
                'data' => __('Failed to sync permissions'),
            ], 500);
        }
    }
}
