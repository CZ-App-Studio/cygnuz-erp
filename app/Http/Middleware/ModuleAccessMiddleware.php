<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ModuleAccessMiddleware
{
    /**
     * Module to role mapping
     */
    protected $moduleRoleMap = [
        'hr' => ['super_admin', 'admin', 'hr_manager', 'hr_executive'],
        'accounting' => ['super_admin', 'admin', 'accounting_manager', 'accounting_executive'],
        'crm' => ['super_admin', 'admin', 'crm_manager', 'sales_manager', 'sales_executive'],
        'project' => ['super_admin', 'admin', 'project_manager'],
        'inventory' => ['super_admin', 'admin', 'inventory_manager'],
        'sales' => ['super_admin', 'admin', 'sales_manager', 'sales_executive'],
        'field' => ['super_admin', 'admin', 'field_employee', 'sales_manager'],
        'organization' => ['super_admin', 'admin', 'hr_manager'],
        'user_management' => ['super_admin', 'admin'],
        'system' => ['super_admin', 'admin'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $userRole = $user->roles()->first();

        if (! $userRole) {
            abort(403, 'No role assigned to user.');
        }

        // Super admin and admin have access to everything
        if (in_array($userRole->name, ['super_admin', 'admin'])) {
            return $next($request);
        }

        // Check if the user's role has access to this module
        if (isset($this->moduleRoleMap[$module])) {
            if (in_array($userRole->name, $this->moduleRoleMap[$module])) {
                return $next($request);
            }
        }

        // If user doesn't have module access, redirect to their dashboard
        return redirect()->route('dashboard')->with('error', __('You do not have access to this module.'));
    }
}
