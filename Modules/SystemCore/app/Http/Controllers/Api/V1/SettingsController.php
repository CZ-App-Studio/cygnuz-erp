<?php

namespace Modules\SystemCore\app\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ModuleSetting;
use App\Models\SystemSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\SystemCore\app\Models\UserSettings;

class SettingsController extends Controller
{
    public function getBasicSettings(): JsonResponse
    {
        $user = auth()->user();

        // Cache the settings for better performance
        $settings = Cache::remember('mobile_basic_settings_'.$user->id, 3600, function () use ($user) {
            $settingsArray = [];

            // Define the settings keys we want to expose to mobile app
            $requiredSettings = [
                'currency' => 'USD',
                'currency_symbol' => '$',
                'currency_position' => 'before',
                'date_format' => 'd/m/Y',
                'time_format' => 'H:i',
                'timezone' => 'UTC',
                'company_name' => config('app.name', 'Company'),
                'company_email' => null,
                'company_phone' => null,
                'company_address' => null,
                'company_logo' => null,
                'fiscal_year_start' => '01-01',
                'week_start' => 'monday',
                'default_language' => 'en',
                'enable_notifications' => true,
                'enable_location_tracking' => false,
                'enable_biometric' => true,
                'max_upload_size' => 10485760, // 10MB in bytes
                'allowed_file_types' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'],
                'maintenance_mode' => false,
                'app_version' => '1.0.0',
                'min_app_version' => '1.0.0',
                'force_update' => false,
            ];

            // Get settings from database
            $dbSettings = SystemSetting::whereIn('key', array_keys($requiredSettings))
                ->where('is_public', true)
                ->pluck('value', 'key')
                ->toArray();

            // Merge with defaults
            foreach ($requiredSettings as $key => $defaultValue) {
                $settingsArray[$key] = $dbSettings[$key] ?? $defaultValue;
            }

            // Get user-specific preferences
            $userPreferences = UserSettings::getUserSettings($user->id);

            // Override with user preferences where applicable
            $userPreferenceKeys = ['language', 'enable_notifications', 'enable_biometric', 'enable_location', 'theme'];
            foreach ($userPreferenceKeys as $key) {
                if (isset($userPreferences[$key])) {
                    // Map enable_location to enable_location_tracking for consistency
                    if ($key === 'enable_location') {
                        $settingsArray['enable_location_tracking'] = filter_var($userPreferences[$key], FILTER_VALIDATE_BOOLEAN);
                    } elseif ($key === 'language') {
                        $settingsArray['default_language'] = $userPreferences[$key];
                    } else {
                        $settingsArray[$key] = filter_var($userPreferences[$key], FILTER_VALIDATE_BOOLEAN);
                    }
                }
            }

            // Add theme preference if set
            if (isset($userPreferences['theme'])) {
                $settingsArray['theme'] = $userPreferences['theme'];
            }

            return $settingsArray;
        });

        return response()->json([
            'success' => true,
            'message' => 'Settings retrieved successfully',
            'data' => $settings,
        ]);
    }

    public function getModuleSettings(): JsonResponse
    {
        // Cache the module settings for better performance
        $moduleData = Cache::remember('mobile_module_settings_'.auth()->id(), 3600, function () {
            // Read module statuses from file
            $moduleStatusFile = base_path('modules_statuses.json');
            $moduleStatuses = [];

            if (file_exists($moduleStatusFile)) {
                $moduleStatuses = json_decode(file_get_contents($moduleStatusFile), true);
            }

            // Map internal module names to mobile app features
            $moduleMapping = [
                'announcements' => isset($moduleStatuses['Announcement']) && $moduleStatuses['Announcement'],
                'payroll' => isset($moduleStatuses['Payroll']) && $moduleStatuses['Payroll'],
                'payslips' => isset($moduleStatuses['Payroll']) && $moduleStatuses['Payroll'],
                'document_management' => isset($moduleStatuses['DocumentManagement']) && $moduleStatuses['DocumentManagement'],
                'loan_management' => isset($moduleStatuses['LoanManagement']) && $moduleStatuses['LoanManagement'],
                'recruitment' => isset($moduleStatuses['Recruitment']) && $moduleStatuses['Recruitment'],
                'training' => isset($moduleStatuses['LMS']) && $moduleStatuses['LMS'],
                'assets' => isset($moduleStatuses['Assets']) && $moduleStatuses['Assets'],
                'calendar' => isset($moduleStatuses['Calendar']) && $moduleStatuses['Calendar'],
                'chat' => isset($moduleStatuses['AiChat']) && $moduleStatuses['AiChat'],
                'video_calling' => isset($moduleStatuses['AgoraCall']) && $moduleStatuses['AgoraCall'],
                'face_attendance' => isset($moduleStatuses['FaceAttendance']) && $moduleStatuses['FaceAttendance'],
                'qr_attendance' => isset($moduleStatuses['QRAttendance']) && $moduleStatuses['QRAttendance'],
                'geofence_attendance' => isset($moduleStatuses['GeofenceSystem']) && $moduleStatuses['GeofenceSystem'],
                'ip_attendance' => isset($moduleStatuses['IpAddressAttendance']) && $moduleStatuses['IpAddressAttendance'],
                'site_attendance' => isset($moduleStatuses['SiteAttendance']) && $moduleStatuses['SiteAttendance'],
                'offline_tracking' => isset($moduleStatuses['OfflineTracking']) && $moduleStatuses['OfflineTracking'],
                'field_tracking' => isset($moduleStatuses['FieldManager']) && $moduleStatuses['FieldManager'],
                'task_management' => isset($moduleStatuses['TaskSystem']) && $moduleStatuses['TaskSystem'],
                'sos' => isset($moduleStatuses['SOS']) && $moduleStatuses['SOS'],
                'digital_id_card' => isset($moduleStatuses['DigitalIdCard']) && $moduleStatuses['DigitalIdCard'],
                'communication_center' => isset($moduleStatuses['CommunicationCenter']) && $moduleStatuses['CommunicationCenter'],
                'notes' => isset($moduleStatuses['Notes']) && $moduleStatuses['Notes'],
                'forms' => isset($moduleStatuses['FormBuilder']) && $moduleStatuses['FormBuilder'],
            ];

            // Get feature flags from module settings
            // $features = [
            //   'enable_chat' => $moduleMapping['chat'],
            //   'enable_video_call' => $moduleMapping['video_calling'],
            //   'enable_voice_call' => $moduleMapping['video_calling'],
            //   'enable_screen_share' => $moduleMapping['video_calling'],
            //   'enable_file_sharing' => true, // Always enabled if document management is on
            //   'enable_location_sharing' => $moduleMapping['geofence_attendance'] || $moduleMapping['field_tracking'],
            //   'enable_push_notifications' => true,
            //   'enable_email_notifications' => true,
            //   'enable_sms_notifications' => false,
            // ];

            // Get additional module-specific settings from database
            $moduleSettings = ModuleSetting::whereIn('module', [
                'Announcement',
                'Payroll',
                'DocumentManagement',
                'Calendar',
                'FaceAttendance',
                'QRAttendance',
                'GeofenceSystem',
            ])->get()->groupBy('module');

            // Process module-specific configurations
            foreach ($moduleSettings as $module => $settings) {
                foreach ($settings as $setting) {
                    // Add any module-specific configurations here if needed
                    if ($setting->key === 'enabled' && ! $setting->value) {
                        // Override file-based status if explicitly disabled in DB
                        $moduleKey = strtolower(str_replace(['_', ' '], '', $module));
                        if (isset($moduleMapping[$moduleKey])) {
                            $moduleMapping[$moduleKey] = false;
                        }
                    }
                }
            }

            return [
                'modules' => $moduleMapping,
                // 'features' => $features,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Modules retrieved successfully',
            'data' => $moduleData,
        ]);
    }

    public function getAllSettings(): JsonResponse
    {
        $basicSettings = json_decode($this->getBasicSettings()->getContent(), true)['data'];
        $moduleSettings = json_decode($this->getModuleSettings()->getContent(), true)['data'];

        return response()->json([
            'success' => true,
            'message' => 'All settings retrieved successfully',
            'data' => [
                'basic' => $basicSettings,
                'modules' => $moduleSettings['modules'],
                // 'features' => $moduleSettings['features'],
            ],
        ]);
    }

    public function updateUserPreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => 'sometimes|string|in:en,ar,es,fr,de',
            'enable_notifications' => 'sometimes|boolean',
            'enable_biometric' => 'sometimes|boolean',
            'enable_location' => 'sometimes|boolean',
            'theme' => 'sometimes|string|in:light,dark,auto',
        ]);

        $user = auth()->user();

        // Store user preferences in database
        foreach ($validated as $key => $value) {
            UserSettings::setSetting($user->id, $key, $value);
        }

        // Clear the cached settings for this user
        Cache::forget('mobile_module_settings_'.$user->id);
        Cache::forget('mobile_basic_settings_'.$user->id);

        return response()->json([
            'success' => true,
            'message' => 'Preferences updated successfully',
        ]);
    }
}
