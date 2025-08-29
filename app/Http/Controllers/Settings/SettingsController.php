<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Controller;
use App\Services\BrandColorService;
use App\Services\Settings\SettingsRegistry;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected SettingsRegistry $settingsRegistry,
        protected BrandColorService $brandColorService
    ) {}

    /**
     * Settings dashboard
     */
    public function index(): View
    {
        $categories = [
            'general' => [
                'title' => __('General Settings'),
                'icon' => 'bx bx-cog',
                'description' => __('Date, time, currency, language and system preferences'),
            ],
            'email' => [
                'title' => __('Email Configuration'),
                'icon' => 'bx bx-envelope',
                'description' => __('Email server and notification settings'),
            ],
            'company' => [
                'title' => __('Company Information'),
                'icon' => 'bx bx-building',
                'description' => __('Company details and contact information'),
            ],
            'attendance' => [
                'title' => __('Attendance Settings'),
                'icon' => 'bx bx-time-five',
                'description' => __('Check-in/out and attendance tracking settings'),
            ],
            'maps' => [
                'title' => __('Maps & Location'),
                'icon' => 'bx bx-map',
                'description' => __('Map provider and location settings'),
            ],
            'branding' => [
                'title' => __('Branding'),
                'icon' => 'bx bx-palette',
                'description' => __('Application branding and appearance'),
            ],
        ];

        // Get module settings
        $moduleSettings = $this->settingsRegistry->getRegisteredModules();

        return view('settings.index', compact('categories', 'moduleSettings'));
    }

    /**
     * Get system settings by category
     */
    public function getSystemSettings(string $category): View
    {
        $settings = $this->settingsService->getByCategory($category);
        $metadata = $this->settingsRegistry->getCategoryMetadata($category);

        return view("settings.system.{$category}", compact('settings', 'metadata'));
    }

    /**
     * Update system settings
     */
    public function updateSystemSettings(Request $request, string $category): JsonResponse
    {
        try {
            DB::beginTransaction();

            $settings = $request->except(['_token', '_method']);

            // Handle file uploads for branding
            if ($category === 'branding') {
                $settings = $this->handleBrandingFileUploads($request, $settings);
            }

            // Get metadata for validation
            $metadata = $this->settingsRegistry->getCategoryMetadata($category);
            $rules = [];

            // Only validate fields that are actually submitted
            foreach ($settings as $key => $value) {
                if (isset($metadata[$key]) && isset($metadata[$key]['validation'])) {
                    $rules[$key] = $metadata[$key]['validation'];
                }
            }

            // Validate if rules exist
            if (! empty($rules)) {
                $validator = Validator::make($settings, $rules);
                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'errors' => $validator->errors(),
                    ], 422);
                }
            }

            // Update settings
            foreach ($settings as $key => $value) {
                // Handle checkbox values
                if ($value === null && isset($metadata[$key]) && $metadata[$key]['type'] === 'boolean') {
                    $value = false;
                }

                $this->settingsService->set($key, $value);
            }

            // Special handling for email settings - update config dynamically
            if ($category === 'email') {
                $this->updateEmailConfig($settings);
            }

            // Special handling for general settings - update timezone
            if ($category === 'general' && isset($settings['default_timezone'])) {
                $this->updateTimezoneConfig($settings['default_timezone']);
            }

            // Special handling for currency - update currency symbol when currency changes
            if ($category === 'general' && isset($settings['default_currency'])) {
                $currencySymbol = \App\Helpers\CurrencyHelper::getSymbol($settings['default_currency']);
                $this->settingsService->set('currency_symbol', $currencySymbol);
            }

            // Special handling for branding - update SCSS files when primary color changes
            if ($category === 'branding' && isset($settings['primary_color'])) {
                $this->brandColorService->updateBrandColor($settings['primary_color']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Settings updated successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating settings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to update settings'),
            ], 500);
        }
    }

    /**
     * Update email configuration dynamically
     */
    private function updateEmailConfig(array $settings): void
    {
        $mailConfigMap = [
            'mail_mailer' => 'mail.default',
            'mail_host' => 'mail.mailers.smtp.host',
            'mail_port' => 'mail.mailers.smtp.port',
            'mail_username' => 'mail.mailers.smtp.username',
            'mail_password' => 'mail.mailers.smtp.password',
            'mail_encryption' => 'mail.mailers.smtp.encryption',
            'mail_from_address' => 'mail.from.address',
            'mail_from_name' => 'mail.from.name',
        ];

        foreach ($mailConfigMap as $settingKey => $configKey) {
            if (isset($settings[$settingKey])) {
                config([$configKey => $settings[$settingKey]]);
            }
        }

        // Clear mail configuration cache to ensure new settings take effect
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }

    /**
     * Update timezone configuration dynamically
     */
    private function updateTimezoneConfig(string $timezone): void
    {
        try {
            // Use the helper to set application timezone
            TimezoneHelper::setApplicationTimezone($timezone);

            // Log the timezone change
            Log::info('Application timezone updated', ['timezone' => $timezone]);
        } catch (\InvalidArgumentException $e) {
            Log::error('Invalid timezone provided', ['timezone' => $timezone, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Search settings
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $results = [];

        // Search system settings
        $systemSettings = $this->settingsService->all();
        foreach ($systemSettings as $key => $value) {
            if (stripos($key, $query) !== false || stripos($value, $query) !== false) {
                $results[] = [
                    'type' => 'system',
                    'key' => $key,
                    'value' => $value,
                    'category' => $this->detectCategory($key),
                ];
            }
        }

        return response()->json($results);
    }

    /**
     * Export settings
     */
    public function exportSettings(): JsonResponse
    {
        $data = [
            'exported_at' => now()->toIso8601String(),
            'system_settings' => $this->settingsService->all()->toArray(),
            'module_settings' => app('module.settings')->getAllGrouped()->toArray(),
        ];

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="settings-'.date('Y-m-d-His').'.json"');
    }

    /**
     * Import settings
     */
    public function importSettings(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        try {
            $content = json_decode($request->file('file')->get(), true);

            if (! isset($content['system_settings'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid settings file format'),
                ], 422);
            }

            DB::beginTransaction();

            // Import system settings
            foreach ($content['system_settings'] as $key => $value) {
                $this->settingsService->set($key, $value);
            }

            // Import module settings if present
            if (isset($content['module_settings'])) {
                $moduleSettings = app('module.settings');
                foreach ($content['module_settings'] as $module => $settings) {
                    $moduleSettings->setMultiple($module, $settings);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Settings imported successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error importing settings: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to import settings'),
            ], 500);
        }
    }

    /**
     * Handle file uploads for branding settings
     */
    private function handleBrandingFileUploads(Request $request, array $settings): array
    {
        // Handle Light Logo
        if ($request->hasFile('brand_logo_light')) {
            $file = $request->file('brand_logo_light');

            if ($file->isValid()) {
                // Ensure directory exists
                $logoPath = public_path('assets/img');
                if (! File::exists($logoPath)) {
                    File::makeDirectory($logoPath, 0755, true);
                }

                // Save as light_logo.png
                $lightLogoPath = $logoPath.'/light_logo.png';
                $file->move($logoPath, 'light_logo.png');

                // Also save as logo.png (copy the light logo)
                $logoPath = $logoPath.'/logo.png';
                File::copy($lightLogoPath, $logoPath);

                // Store the path in settings
                $settings['brand_logo_light'] = 'assets/img/light_logo.png';

                Log::info('Light logo uploaded and saved to both light_logo.png and logo.png');
            }
        }

        // Handle Dark Logo
        if ($request->hasFile('brand_logo_dark')) {
            $file = $request->file('brand_logo_dark');

            if ($file->isValid()) {
                // Ensure directory exists
                $logoPath = public_path('assets/img');
                if (! File::exists($logoPath)) {
                    File::makeDirectory($logoPath, 0755, true);
                }

                // Save as dark_logo.png
                $file->move($logoPath, 'dark_logo.png');

                // Store the path in settings
                $settings['brand_logo_dark'] = 'assets/img/dark_logo.png';

                Log::info('Dark logo uploaded and saved');
            }
        }

        // Handle Favicon
        if ($request->hasFile('brand_favicon')) {
            $file = $request->file('brand_favicon');

            if ($file->isValid()) {
                // Ensure directory exists
                $faviconPath = public_path('assets/img/favicon');
                if (! File::exists($faviconPath)) {
                    File::makeDirectory($faviconPath, 0755, true);
                }

                // Determine the extension
                $extension = $file->getClientOriginalExtension();
                if ($extension !== 'ico') {
                    // If not ICO, we'll still save it but rename to favicon.ico
                    Log::warning('Favicon uploaded is not ICO format, saving as favicon.ico anyway');
                }

                // Save as favicon.ico
                $file->move($faviconPath, 'favicon.ico');

                // Store the path in settings
                $settings['brand_favicon'] = 'assets/img/favicon/favicon.ico';

                Log::info('Favicon uploaded and saved');
            }
        }

        // Remove fields from settings if not uploaded (to avoid overwriting with null)
        $fileFields = ['brand_logo_light', 'brand_logo_dark', 'brand_favicon'];
        foreach ($fileFields as $field) {
            if (! $request->hasFile($field) && array_key_exists($field, $settings)) {
                unset($settings[$field]);
            }
        }

        return $settings;
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration(Request $request): JsonResponse
    {
        try {
            $testEmail = $request->get('test_email', auth()->user()->email);

            // Load current email settings from database
            $emailSettings = $this->settingsService->getByCategory('email');

            // Temporarily set config for this request
            if (! empty($emailSettings)) {
                $this->updateEmailConfig($emailSettings->toArray());
            }

            // Send test email
            Mail::raw(__('This is a test email from your ERP system. If you received this email, your email configuration is working correctly.'), function ($message) use ($testEmail) {
                $message->to($testEmail)
                    ->subject(__('Test Email - Configuration Successful'));
            });

            return response()->json([
                'success' => true,
                'message' => __('Test email sent successfully to :email', ['email' => $testEmail]),
            ]);

        } catch (\Exception $e) {
            Log::error('Email test failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to send test email. Please check your configuration.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect category from key
     */
    private function detectCategory(string $key): string
    {
        $prefixMap = [
            'app_' => 'general',
            'company_' => 'company',
            'currency' => 'general',
            'default_' => 'general',
            'date_format' => 'general',
            'time_format' => 'general',
            'decimal' => 'general',
            'thousand' => 'general',
            'map' => 'maps',
            'ai_' => 'ai',
            'chat_gpt' => 'ai',
            'payroll_' => 'payroll',
            'mail_' => 'email',
        ];

        foreach ($prefixMap as $prefix => $category) {
            if (str_starts_with($key, $prefix)) {
                return $category;
            }
        }

        return 'general';
    }
}
