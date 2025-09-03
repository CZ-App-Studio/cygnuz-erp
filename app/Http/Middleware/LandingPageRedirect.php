<?php

namespace App\Http\Middleware;

use App\Services\AddonService\IAddonService;
use Closure;
use Illuminate\Http\Request;

class LandingPageRedirect
{
    protected $addonService;

    public function __construct(IAddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // If the request is for the root path and user is not authenticated
        if ($request->path() === '/' && ! auth()->check()) {
            // Check if LandingPage module is enabled
            if ($this->addonService->isAddonEnabled('LandingPage')) {
                return redirect()->route('landingPage.index');
            }
        }

        return $next($request);
    }
}
