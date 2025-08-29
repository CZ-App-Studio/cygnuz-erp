<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetEmployeeLayoutMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        // Check if a user is authenticated
        if (auth()->check()) {
            // Check if the user has the employee, field_employee, or tenant role
            if (!auth()->user()->hasRole(['super_admin', 'admin'])) {
                // Define the pageConfigs for Employee Self Service and Tenant users
                $pageConfigs = ['myLayout' => 'horizontal'];

                // Share this variable with all views rendered during this request cycle
                View::share('pageConfigs', $pageConfigs);
            }
        }

        // Continue processing the request
        return $next($request);
    }
}