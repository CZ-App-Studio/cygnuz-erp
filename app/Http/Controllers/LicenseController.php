<?php

namespace App\Http\Controllers;

use App\Services\AddonService\AddonService;
use App\Services\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class LicenseController extends Controller
{
    private $licenseService;

    private $addonService;

    public function __construct(LicenseService $licenseService, AddonService $addonService)
    {
        $this->licenseService = $licenseService;
        $this->addonService = $addonService;
    }

    /**
     * Show license activation page
     */
    public function index()
    {
        $customerEmail = Session::get('customer_license_email');
        $enabledAddons = $this->addonService->getEnabledAddons();
        $licenseInfo = null;

        if ($customerEmail) {
            $licenseInfo = $this->licenseService->getLicenseInfo($customerEmail);
        }

        return view('license.index', [
            'customerEmail' => $customerEmail,
            'enabledAddons' => $enabledAddons,
            'licenseInfo' => $licenseInfo,
        ]);
    }

    /**
     * Activate main application license
     */
    public function activateMainApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
            'purchase_code' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid email address.',
                'errors' => $validator->errors(),
            ]);
        }

        $customerEmail = $request->input('customer_email');
        $purchaseCode = $request->input('purchase_code');

        $result = $this->licenseService->activateMainApplication($customerEmail, $purchaseCode);

        if ($result['success']) {
            // Store customer email in session for future use
            Session::put('customer_license_email', $customerEmail);
        }

        return response()->json($result);
    }

    /**
     * Activate addon license
     */
    public function activateAddon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
            'addon_name' => 'required|string',
            'purchase_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide all required information.',
                'errors' => $validator->errors(),
            ]);
        }

        $customerEmail = $request->input('customer_email');
        $addonName = $request->input('addon_name');
        $purchaseCode = $request->input('purchase_code');

        $result = $this->licenseService->activateAddon($addonName, $customerEmail, $purchaseCode);

        if ($result['success']) {
            // Store customer email in session for future use
            Session::put('customer_license_email', $customerEmail);
        }

        return response()->json($result);
    }

    /**
     * Validate license status
     */
    public function validateLicense(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
            'addon_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters.',
                'errors' => $validator->errors(),
            ]);
        }

        $customerEmail = $request->input('customer_email');
        $addonName = $request->input('addon_name');

        if ($addonName) {
            $isValid = $this->licenseService->validateAddon($addonName, $customerEmail);
        } else {
            $isValid = $this->licenseService->validateMainApplication($customerEmail);
        }

        return response()->json([
            'success' => true,
            'valid' => $isValid,
            'message' => $isValid ? 'License is valid' : 'License is not valid',
        ]);
    }

    /**
     * Check subscription status
     */
    public function subscriptionStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
            'addon_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters.',
                'errors' => $validator->errors(),
            ]);
        }

        $customerEmail = $request->input('customer_email');
        $addonName = $request->input('addon_name');

        $result = $this->licenseService->checkSubscriptionStatus($customerEmail, $addonName);

        return response()->json($result);
    }

    /**
     * Get license information
     */
    public function licenseInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid email address.',
                'errors' => $validator->errors(),
            ]);
        }

        $customerEmail = $request->input('customer_email');
        $result = $this->licenseService->getLicenseInfo($customerEmail);

        return response()->json($result);
    }

    /**
     * Set customer email for license validation
     */
    public function setCustomerEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid email address.',
                'errors' => $validator->errors(),
            ]);
        }

        $customerEmail = $request->input('customer_email');
        Session::put('customer_license_email', $customerEmail);

        return response()->json([
            'success' => true,
            'message' => 'Customer email set successfully',
        ]);
    }

    /**
     * Clear license cache
     */
    public function clearCache(Request $request)
    {
        $customerEmail = Session::get('customer_license_email');

        if (! $customerEmail) {
            return response()->json([
                'success' => false,
                'message' => 'No customer email found in session',
            ]);
        }

        $this->licenseService->clearLicenseCache($customerEmail);

        return response()->json([
            'success' => true,
            'message' => 'License cache cleared successfully',
        ]);
    }
}
