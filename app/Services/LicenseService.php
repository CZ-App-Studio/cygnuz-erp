<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    private const CACHE_TTL = 3600; // 1 hour

    private const LICENSE_SERVER_URL = 'http://czappstudio.local:8890/wp-json/license-activation/v1';

    private const MAIN_PRODUCT_ID = '18310'; // Main ERP Application Product ID

    /**
     * Validate main application license
     */
    public function validateMainApplication(string $customerEmail): bool
    {
        $cacheKey = "main_license_validation_{$customerEmail}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($customerEmail) {
            try {
                $response = Http::timeout(10)->post(self::LICENSE_SERVER_URL.'/validate-main-app', [
                    'email' => $customerEmail,
                    'domain' => $this->getCurrentDomain(),
                    'app_version' => config('app.version', '1.0.0'),
                    'server_info' => $this->getServerInfo(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['valid'] ?? false;
                }

                Log::warning('License validation failed', [
                    'email' => $customerEmail,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            } catch (Exception $e) {
                Log::error('License validation error', [
                    'email' => $customerEmail,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }

    /**
     * Validate addon license
     */
    public function validateAddon(string $addonName, string $customerEmail): bool
    {
        $cacheKey = "addon_license_{$addonName}_{$customerEmail}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($addonName, $customerEmail) {
            try {
                $response = Http::timeout(10)->post(self::LICENSE_SERVER_URL.'/validate-addon', [
                    'addon_name' => $addonName,
                    'email' => $customerEmail,
                    'domain' => $this->getCurrentDomain(),
                    'app_version' => config('app.version', '1.0.0'),
                    'server_info' => $this->getServerInfo(),
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    return $data['valid'] ?? false;
                }

                Log::warning('Addon license validation failed', [
                    'addon' => $addonName,
                    'email' => $customerEmail,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            } catch (Exception $e) {
                Log::error('Addon license validation error', [
                    'addon' => $addonName,
                    'email' => $customerEmail,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        });
    }

    /**
     * Activate main application license
     */
    public function activateMainApplication(string $customerEmail, ?string $purchaseCode = null): array
    {
        try {
            $response = Http::timeout(30)->post(self::LICENSE_SERVER_URL.'/activate-main-app', [
                'email' => $customerEmail,
                'purchase_code' => $purchaseCode,
                'domain' => $this->getCurrentDomain(),
                'app_version' => config('app.version', '1.0.0'),
                'server_info' => $this->getServerInfo(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    // Clear cache after successful activation
                    Cache::forget("main_license_validation_{$customerEmail}");

                    return [
                        'success' => true,
                        'activation_code' => $data['activation_code'] ?? null,
                        'message' => $data['message'] ?? 'Application activated successfully',
                    ];
                }
            }

            $errorData = $response->json();

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Activation failed',
            ];

        } catch (Exception $e) {
            Log::error('Main app activation error', [
                'email' => $customerEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred during activation',
            ];
        }
    }

    /**
     * Activate addon license
     */
    public function activateAddon(string $addonName, string $customerEmail, string $purchaseCode): array
    {
        try {
            $response = Http::timeout(30)->post(self::LICENSE_SERVER_URL.'/activate-addon', [
                'addon_name' => $addonName,
                'email' => $customerEmail,
                'purchase_code' => $purchaseCode,
                'domain' => $this->getCurrentDomain(),
                'app_version' => config('app.version', '1.0.0'),
                'server_info' => $this->getServerInfo(),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    // Clear cache after successful activation
                    Cache::forget("addon_license_{$addonName}_{$customerEmail}");

                    return [
                        'success' => true,
                        'activation_code' => $data['activation_code'] ?? null,
                        'message' => $data['message'] ?? 'Addon activated successfully',
                    ];
                }
            }

            $errorData = $response->json();

            return [
                'success' => false,
                'message' => $errorData['message'] ?? 'Addon activation failed',
            ];

        } catch (Exception $e) {
            Log::error('Addon activation error', [
                'addon' => $addonName,
                'email' => $customerEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred during activation',
            ];
        }
    }

    /**
     * Check subscription status
     */
    public function checkSubscriptionStatus(string $customerEmail, ?string $addonName = null): array
    {
        try {
            $params = [
                'email' => $customerEmail,
                'domain' => $this->getCurrentDomain(),
            ];

            if ($addonName) {
                $params['addon_name'] = $addonName;
            }

            $response = Http::timeout(10)->get(self::LICENSE_SERVER_URL.'/subscription-status', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Unable to check subscription status',
            ];

        } catch (Exception $e) {
            Log::error('Subscription status check error', [
                'email' => $customerEmail,
                'addon' => $addonName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred',
            ];
        }
    }

    /**
     * Get current domain
     */
    private function getCurrentDomain(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        if (isset($_SERVER['SERVER_NAME'])) {
            return $_SERVER['SERVER_NAME'];
        }

        return parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';
    }

    /**
     * Get server information for license validation
     */
    private function getServerInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS,
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Clear all license caches for a customer
     */
    public function clearLicenseCache(string $customerEmail): void
    {
        Cache::forget("main_license_validation_{$customerEmail}");

        // Get all addon names and clear their caches
        $addons = app(\App\Services\AddonService\AddonService::class)->getEnabledAddons();
        foreach ($addons as $addon) {
            Cache::forget("addon_license_{$addon}_{$customerEmail}");
        }
    }

    /**
     * Get license information
     */
    public function getLicenseInfo(string $customerEmail): array
    {
        try {
            $response = Http::timeout(10)->get(self::LICENSE_SERVER_URL.'/license-info', [
                'email' => $customerEmail,
                'domain' => $this->getCurrentDomain(),
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => 'Unable to retrieve license information',
            ];

        } catch (Exception $e) {
            Log::error('License info retrieval error', [
                'email' => $customerEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Network error occurred',
            ];
        }
    }
}
