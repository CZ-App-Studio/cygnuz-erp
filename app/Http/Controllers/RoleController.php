<?php

namespace App\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    protected $builtInRoles = ['super_admin', 'admin', 'field_employee', 'office_employee'];

    public function index()
    {
        if (! auth()->user()->can('view-roles')) {
            abort(403);
        }

        return view('roles.index');
    }

    /**
     * Get roles data for DataTable
     */
    public function indexAjax(Request $request)
    {
        if (! auth()->user()->can('view-roles')) {
            abort(403);
        }

        $query = Role::withCount(['users', 'permissions']);

        return DataTables::of($query)
            ->addColumn('users_count', function ($role) {
                return $role->users_count;
            })
            ->addColumn('permissions_count', function ($role) {
                return $role->permissions_count;
            })
            ->addColumn('status', function ($role) {
                $isBuiltIn = in_array($role->name, $this->builtInRoles);
                if ($isBuiltIn) {
                    return '<span class="badge bg-label-primary">Built-in</span>';
                }

                return '<span class="badge bg-label-success">Custom</span>';
            })
            ->addColumn('actions', function ($role) {
                $actions = [];

                if (auth()->user()->can('manage-role-permissions')) {
                    $actions[] = [
                        'label' => __('Manage Permissions'),
                        'icon' => 'bx bx-lock',
                        'url' => route('roles.permissions', $role->id),
                    ];
                }

                if (auth()->user()->can('edit-roles') && ! in_array($role->name, $this->builtInRoles)) {
                    $actions[] = [
                        'label' => __('Edit'),
                        'icon' => 'bx bx-edit',
                        'onclick' => "editRole({$role->id})",
                    ];
                }

                if (auth()->user()->can('delete-roles') && ! in_array($role->name, $this->builtInRoles)) {
                    $actions[] = [
                        'divider' => true,
                    ];
                    $actions[] = [
                        'label' => __('Delete'),
                        'icon' => 'bx bx-trash',
                        'onclick' => "deleteRole({$role->id})",
                    ];
                }

                return view('components.datatable-actions', [
                    'id' => $role->id,
                    'actions' => $actions,
                ])->render();
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }

    /**
     * Store a new role
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('create-roles')) {
            abort(403);
        }

        if (env('APP_DEMO')) {
            return Error::response('This feature is disabled in demo mode');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $request->name,
                'guard_name' => 'web',
                'description' => $request->description,
            ]);

            DB::commit();

            return Success::response('Role created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role creation error: '.$e->getMessage());

            return Error::response('Failed to create role');
        }
    }

    /**
     * Get role details for editing
     */
    public function edit($id)
    {
        if (! auth()->user()->can('edit-roles')) {
            abort(403);
        }

        $role = Role::findOrFail($id);

        if (in_array($role->name, $this->builtInRoles)) {
            return Error::response('Built-in roles cannot be edited');
        }

        return response()->json([
            'status' => 'success',
            'data' => $role,
        ]);
    }

    /**
     * Update role
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('edit-roles')) {
            abort(403);
        }

        if (env('APP_DEMO')) {
            return Error::response('This feature is disabled in demo mode');
        }

        $role = Role::findOrFail($id);

        if (in_array($role->name, $this->builtInRoles)) {
            return Error::response('Built-in roles cannot be edited');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,'.$id,
            'description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first());
        }

        try {
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return Success::response('Role updated successfully');
        } catch (\Exception $e) {
            Log::error('Role update error: '.$e->getMessage());

            return Error::response('Failed to update role');
        }
    }

    /**
     * Delete role
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('delete-roles')) {
            abort(403);
        }

        if (env('APP_DEMO')) {
            return Error::response('This feature is disabled in demo mode');
        }

        $role = Role::find($id);

        if (! $role) {
            return Error::response('Role not found');
        }

        if ($role->users->count() > 0) {
            return Error::response('Role has users assigned to it');
        }

        if (in_array($role->name, $this->builtInRoles)) {
            return Error::response('Built-in roles cannot be deleted');
        }

        $role->delete();

        return Success::response('Role deleted successfully');
    }

    /**
     * Show role permissions management page
     */
    public function permissions($id)
    {
        if (! auth()->user()->can('manage-role-permissions')) {
            abort(403);
        }

        $role = Role::findOrFail($id);

        // Get all permissions grouped by module
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        // Get role's current permissions
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update role permissions
     */
    public function updatePermissions(Request $request, $id)
    {
        if (! auth()->user()->can('manage-role-permissions')) {
            abort(403);
        }

        if (env('APP_DEMO')) {
            return Error::response('This feature is disabled in demo mode');
        }

        $role = Role::findOrFail($id);

        try {
            DB::beginTransaction();

            // Log request data for debugging
            Log::info('Role permissions update request', [
                'role_id' => $id,
                'role_name' => $role->name,
                'permissions_count' => count($request->input('permissions', [])),
                'permissions' => $request->input('permissions', []),
                'request_all' => $request->all(),
            ]);

            // Sync permissions
            $permissionIds = $request->input('permissions', []);

            // Convert permission IDs to permission objects
            $permissions = Permission::whereIn('id', $permissionIds)->get();

            // Validate all permissions exist
            if (count($permissionIds) !== $permissions->count()) {
                $foundIds = $permissions->pluck('id')->toArray();
                $invalidIds = array_diff($permissionIds, $foundIds);
                Log::error('Invalid permission IDs detected', ['invalid_ids' => $invalidIds]);

                return Error::response('Invalid permission IDs: '.implode(', ', $invalidIds));
            }

            // Sync permissions using the permission objects
            $role->syncPermissions($permissions);

            DB::commit();

            Log::info('Role permissions updated successfully', [
                'role_id' => $id,
                'permissions_count' => count($permissions),
            ]);

            return Success::response('Role permissions updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Role permissions update error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all(),
            ]);

            return Error::response('Failed to update role permissions: '.$e->getMessage());
        }
    }
}
