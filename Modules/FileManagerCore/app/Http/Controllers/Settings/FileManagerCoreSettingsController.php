<?php

namespace Modules\FileManagerCore\app\Http\Controllers\Settings;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Http\Request;
use Modules\FileManagerCore\app\Settings\FileManagerCoreSettings;

class FileManagerCoreSettingsController extends Controller
{
    protected ModuleSettingsService $settingsService;

    protected FileManagerCoreSettings $moduleSettings;

    public function __construct(
        ModuleSettingsService $settingsService,
        FileManagerCoreSettings $moduleSettings
    ) {
        $this->settingsService = $settingsService;
        $this->moduleSettings = $moduleSettings;

        // Apply permission middleware
        $this->middleware('permission:manage-filemanagercore-settings', ['only' => ['index', 'getSetting', 'update', 'resetToDefaults']]);
    }

    /**
     * Display FileManagerCore settings
     */
    public function index()
    {
        $definition = $this->moduleSettings->getSettingsDefinition();
        $currentValues = $this->moduleSettings->getCurrentValues();
        $moduleName = $this->moduleSettings->getModuleName();
        $moduleDescription = $this->moduleSettings->getModuleDescription();
        $moduleIcon = $this->moduleSettings->getModuleIcon();

        return view('filemanagercore::settings.index', compact(
            'definition',
            'currentValues',
            'moduleName',
            'moduleDescription',
            'moduleIcon'
        ));
    }

    /**
     * Update FileManagerCore settings
     */
    public function update(Request $request)
    {
        try {
            $data = $request->except(['_token', '_method']);

            // Validate the settings
            $validation = $this->moduleSettings->validateSettings($data);

            if (! $validation['valid']) {
                return response()->json(
                    new Error(
                        'Validation failed',
                        'VALIDATION_ERROR',
                        $validation['errors']
                    ),
                    422
                );
            }

            // Save the settings
            $saved = $this->moduleSettings->saveSettings($data);

            if (! $saved) {
                return response()->json(
                    new Error(
                        'Failed to save settings',
                        'SAVE_ERROR'
                    ),
                    500
                );
            }

            return response()->json(
                new Success(
                    'FileManagerCore settings updated successfully',
                    null,
                    'SETTINGS_UPDATED'
                )
            );

        } catch (\Exception $e) {
            \Log::error('FileManagerCore settings update failed', [
                'error' => $e->getMessage(),
                'data' => $data ?? null,
            ]);

            return response()->json(
                new Error(
                    'An error occurred while updating settings',
                    'SYSTEM_ERROR'
                ),
                500
            );
        }
    }

    /**
     * Get a specific setting value
     */
    public function getSetting(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
        ]);

        try {
            $value = $this->settingsService->get('FileManagerCore', $request->key);

            return response()->json(
                new Success(
                    'Setting retrieved successfully',
                    ['key' => $request->key, 'value' => $value],
                    'SETTING_RETRIEVED'
                )
            );
        } catch (\Exception $e) {
            return response()->json(
                new Error(
                    'Setting not found',
                    'SETTING_NOT_FOUND'
                ),
                404
            );
        }
    }

    /**
     * Reset all settings to defaults
     */
    public function resetToDefaults(Request $request)
    {
        try {
            $defaults = $this->moduleSettings->getDefaultValues();

            foreach ($defaults as $key => $value) {
                $this->settingsService->set('FileManagerCore', $key, $value);
            }

            return response()->json(
                new Success(
                    'FileManagerCore settings reset to defaults successfully',
                    null,
                    'SETTINGS_RESET'
                )
            );

        } catch (\Exception $e) {
            \Log::error('FileManagerCore settings reset failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                new Error(
                    'An error occurred while resetting settings',
                    'SYSTEM_ERROR'
                ),
                500
            );
        }
    }

    /**
     * Get storage statistics
     */
    public function getStorageStats()
    {
        try {
            // This would typically calculate actual storage usage
            // For now, return sample data
            $stats = [
                'total_files' => 0,
                'total_size' => 0,
                'users_count' => 0,
                'departments_count' => 0,
                'thumbnails_count' => 0,
                'versions_count' => 0,
            ];

            return response()->json(
                new Success(
                    'Storage statistics retrieved successfully',
                    $stats,
                    'STATS_RETRIEVED'
                )
            );
        } catch (\Exception $e) {
            return response()->json(
                new Error(
                    'Failed to retrieve storage statistics',
                    'STATS_ERROR'
                ),
                500
            );
        }
    }

    /**
     * Test settings configuration
     */
    public function testConfiguration(Request $request)
    {
        try {
            $tests = [];

            // Test storage disk access
            $defaultDisk = $this->settingsService->get('FileManagerCore', 'filemanager_default_disk', 'public');
            $tests['storage_disk'] = [
                'name' => 'Storage Disk Access',
                'status' => \Storage::disk($defaultDisk)->exists('.') ? 'passed' : 'failed',
                'message' => "Testing access to '{$defaultDisk}' disk",
            ];

            // Test file size limits
            $maxSize = $this->settingsService->get('FileManagerCore', 'filemanager_max_file_size', 10240);
            $tests['file_size_limit'] = [
                'name' => 'File Size Limit',
                'status' => is_numeric($maxSize) && $maxSize > 0 ? 'passed' : 'failed',
                'message' => 'Maximum file size: '.number_format($maxSize / 1024, 2).' MB',
            ];

            // Test thumbnail configuration
            $thumbnailEnabled = $this->settingsService->get('FileManagerCore', 'filemanager_thumbnail_enabled', true);
            $tests['thumbnail_config'] = [
                'name' => 'Thumbnail Configuration',
                'status' => 'passed',
                'message' => $thumbnailEnabled ? 'Thumbnails enabled' : 'Thumbnails disabled',
            ];

            return response()->json(
                new Success(
                    'Configuration test completed',
                    $tests,
                    'CONFIG_TESTED'
                )
            );
        } catch (\Exception $e) {
            return response()->json(
                new Error(
                    'Configuration test failed',
                    'TEST_ERROR'
                ),
                500
            );
        }
    }
}
