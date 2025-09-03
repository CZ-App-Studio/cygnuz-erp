<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSelfServiceAccess
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that all self-service routes automatically use the authenticated user's ID
     * and prevents any attempt to access other users' data through self-service endpoints.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ensure user is authenticated
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'Please login to access self-service features.');
        }

        // Force the user_id to be the authenticated user's ID
        // This ensures no manipulation of user_id in requests
        $request->merge(['user_id' => Auth::id()]);

        // If the route has a user_id parameter, ensure it matches the authenticated user
        if ($request->route('user_id') && $request->route('user_id') != Auth::id()) {
            abort(403, 'You can only access your own information in self-service.');
        }

        // If the request has a user_id in the body/query, ensure it matches
        if ($request->has('user_id') && $request->input('user_id') != Auth::id()) {
            // Override any user_id in the request with the authenticated user's ID
            $request->merge(['user_id' => Auth::id()]);
        }

        // Add a flag to indicate this is a self-service request
        $request->merge(['is_self_service' => true]);

        return $next($request);
    }
}
