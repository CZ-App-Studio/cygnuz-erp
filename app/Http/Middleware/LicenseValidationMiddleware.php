<?php

namespace App\Http\Middleware;

use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class LicenseValidationMiddleware
{
    private $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $addonName = null)
    {
        // Skip validation in demo mode
        if (config('app.demo', false)) {
            return $next($request);
        }

        // Get customer email from session or user
        $customerEmail = $this->getCustomerEmail();

        if (! $customerEmail) {
            return $this->redirectToActivation('Please provide your customer email for license validation.');
        }

        // Validate main application license
        if (! $this->licenseService->validateMainApplication($customerEmail)) {
            return $this->redirectToActivation('Your main application license is not valid. Please activate your license.');
        }

        // If specific addon validation is required
        if ($addonName) {
            if (! $this->licenseService->validateAddon($addonName, $customerEmail)) {
                return $this->redirectToActivation("The {$addonName} addon is not licensed for your account. Please purchase and activate this addon.");
            }
        }

        return $next($request);
    }

    /**
     * Get customer email from various sources
     */
    private function getCustomerEmail(): ?string
    {
        // First check session for stored customer email
        if (Session::has('customer_license_email')) {
            return Session::get('customer_license_email');
        }

        // Check authenticated user email
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->email) {
                return $user->email;
            }
        }

        // Check if there's a system-wide license email set
        if (config('app.license_email')) {
            return config('app.license_email');
        }

        return null;
    }

    /**
     * Redirect to license activation page
     */
    private function redirectToActivation(string $message)
    {
        return redirect()->route('license.activation')
            ->with('error', $message)
            ->with('intended_url', request()->fullUrl());
    }
}
