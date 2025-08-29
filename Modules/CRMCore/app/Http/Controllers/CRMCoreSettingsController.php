<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\CRMCore\app\Settings\CRMCoreSettings;
use App\Services\Settings\ModuleSettingsService;

class CRMCoreSettingsController extends Controller
{
    protected CRMCoreSettings $crmCoreSettings;
    protected ModuleSettingsService $settingsService;

    public function __construct(CRMCoreSettings $crmCoreSettings, ModuleSettingsService $settingsService)
    {
        $this->crmCoreSettings = $crmCoreSettings;
        $this->settingsService = $settingsService;
        $this->middleware('permission:view-crm-settings', ['only' => ['index', 'show']]);
        $this->middleware('permission:manage-crm-settings', ['only' => ['update', 'reset', 'updateSingleSetting']]);
        $this->middleware('permission:export-crm-settings', ['only' => ['export']]);
        $this->middleware('permission:import-crm-settings', ['only' => ['import']]);
    }

    public function index()
    {
        return view('crmcore::crm-settings.index');
    }

    public function show()
    {
        try {
            $settings = $this->crmCoreSettings->getCurrentValues();
            $settingsStructure = $this->crmCoreSettings->getSettingsDefinition();
            
            return Success::response([
                'settings' => $settings,
                'structure' => $settingsStructure,
                'module_info' => [
                    'name' => $this->crmCoreSettings->getModuleName(),
                    'description' => $this->crmCoreSettings->getModuleDescription(),
                    'icon' => $this->crmCoreSettings->getModuleIcon(),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('CRMCoreSettings show error: ' . $e->getMessage());
            return Error::response(__('Failed to load CRM settings.'));
        }
    }

    public function update(Request $request)
    {
        try {
            // Get all settings structure for validation
            $settingsStructure = $this->crmCoreSettings->getSettingsDefinition();
            $validationRules = [];
            
            // Build validation rules from settings structure
            foreach ($settingsStructure as $groupSettings) {
                foreach ($groupSettings as $key => $setting) {
                    if (isset($setting['validation'])) {
                        $validationRules[$key] = $setting['validation'];
                    }
                }
            }

            // Validate the request
            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return Error::response($validator->errors()->first(), 422);
            }

            DB::beginTransaction();
            
            // Update each setting
            foreach ($request->all() as $key => $value) {
                if ($key !== '_token' && $key !== '_method') {
                    // Convert boolean strings to actual boolean values
                    if (in_array($value, ['true', 'false'])) {
                        $value = $value === 'true';
                    }
                    
                    $this->settingsService->set('CRMCore', $key, $value);
                }
            }

            DB::commit();
            
            return Success::response([
                'message' => __('CRM settings updated successfully!'),
                'settings' => $this->crmCoreSettings->getCurrentValues()
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CRMCoreSettings update failed: ' . $e->getMessage());
            return Error::response(__('Failed to update CRM settings. Please try again.'));
        }
    }

    public function reset()
    {
        // Additional permission check for reset operation
        abort_if(!auth()->user()->can('manage-crm-settings'), 403, 'You do not have permission to reset CRM settings.');
        
        try {
            DB::beginTransaction();
            
            // Reset all settings to default values
            $settingsStructure = $this->crmCoreSettings->getSettingsDefinition();
            
            foreach ($settingsStructure as $groupSettings) {
                foreach ($groupSettings as $key => $setting) {
                    if (isset($setting['default'])) {
                        $this->settingsService->set('CRMCore', $key, $setting['default']);
                    }
                }
            }

            DB::commit();
            
            return Success::response([
                'message' => __('CRM settings reset to default values successfully!'),
                'settings' => $this->crmCoreSettings->getCurrentValues()
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CRMCoreSettings reset failed: ' . $e->getMessage());
            return Error::response(__('Failed to reset CRM settings. Please try again.'));
        }
    }

    public function export()
    {
        // Check export permission
        abort_if(!auth()->user()->can('export-crm-settings'), 403, 'You do not have permission to export CRM settings.');
        
        try {
            $settings = $this->crmCoreSettings->getCurrentValues();
            $settingsStructure = $this->crmCoreSettings->getSettingsDefinition();
            
            $exportData = [
                'module' => 'CRMCore',
                'exported_at' => now()->toISOString(),
                'settings' => $settings,
                'structure' => $settingsStructure
            ];
            
            $fileName = 'crm_settings_' . now()->format('Y-m-d_H-i-s') . '.json';
            
            return response()->json($exportData)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
        } catch (Exception $e) {
            Log::error('CRMCoreSettings export failed: ' . $e->getMessage());
            return Error::response(__('Failed to export CRM settings.'));
        }
    }

    public function import(Request $request)
    {
        // Check import permission
        abort_if(!auth()->user()->can('import-crm-settings'), 403, 'You do not have permission to import CRM settings.');
        
        $validator = Validator::make($request->all(), [
            'settings_file' => 'required|file|mimes:json|max:2048',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            $file = $request->file('settings_file');
            $content = file_get_contents($file->getRealPath());
            $importData = json_decode($content, true);

            if (!$importData || !isset($importData['settings'])) {
                return Error::response(__('Invalid settings file format.'));
            }

            if (isset($importData['module']) && $importData['module'] !== 'CRMCore') {
                return Error::response(__('Settings file is not for CRM module.'));
            }

            DB::beginTransaction();

            // Get current settings structure for validation
            $settingsStructure = $this->crmCoreSettings->getSettingsDefinition();
            $validKeys = [];
            
            foreach ($settingsStructure as $groupSettings) {
                $validKeys = array_merge($validKeys, array_keys($groupSettings));
            }

            // Import only valid settings
            foreach ($importData['settings'] as $key => $value) {
                if (in_array($key, $validKeys)) {
                    $this->settingsService->set('CRMCore', $key, $value);
                }
            }

            DB::commit();

            return Success::response([
                'message' => __('CRM settings imported successfully!'),
                'settings' => $this->crmCoreSettings->getCurrentValues()
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('CRMCoreSettings import failed: ' . $e->getMessage());
            return Error::response(__('Failed to import CRM settings. Please try again.'));
        }
    }

    public function updateSingleSetting(Request $request)
    {
        // Check manage permission
        abort_if(!auth()->user()->can('manage-crm-settings'), 403, 'You do not have permission to manage CRM settings.');
        
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'value' => 'required',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            $key = $request->key;
            $value = $request->value;
            
            // Convert boolean strings to actual boolean values
            if (in_array($value, ['true', 'false'])) {
                $value = $value === 'true';
            }

            // Validate against settings structure
            $settingsStructure = $this->crmCoreSettings->getSettingsDefinition();
            $isValidKey = false;
            
            foreach ($settingsStructure as $groupSettings) {
                if (array_key_exists($key, $groupSettings)) {
                    $isValidKey = true;
                    
                    // Validate the value if validation rules exist
                    if (isset($groupSettings[$key]['validation'])) {
                        $valueValidator = Validator::make(['value' => $value], [
                            'value' => $groupSettings[$key]['validation']
                        ]);
                        
                        if ($valueValidator->fails()) {
                            return Error::response($valueValidator->errors()->first(), 422);
                        }
                    }
                    break;
                }
            }

            if (!$isValidKey) {
                return Error::response(__('Invalid setting key.'));
            }

            $this->settingsService->set('CRMCore', $key, $value);

            return Success::response([
                'message' => __('Setting updated successfully!'),
                'key' => $key,
                'value' => $value
            ]);
        } catch (Exception $e) {
            Log::error('CRMCoreSettings updateSingleSetting failed: ' . $e->getMessage());
            return Error::response(__('Failed to update setting. Please try again.'));
        }
    }

    /**
     * Get settings data for other controllers to use
     */
    public function getSettings()
    {
        try {
            $settings = $this->crmCoreSettings->getCurrentValues();
            return Success::response($settings);
        } catch (Exception $e) {
            Log::error('CRMCoreSettings getSettings error: ' . $e->getMessage());
            return Error::response(__('Failed to load CRM settings.'));
        }
    }

    /**
     * Get specific setting values for quick access
     */
    public function getSetting(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Error::response($validator->errors()->first(), 422);
        }

        try {
            $key = $request->key;
            $defaults = $this->crmCoreSettings->getDefaultValues();
            $value = $this->settingsService->get('CRMCore', $key, $defaults[$key] ?? null);

            return Success::response([
                'key' => $key,
                'value' => $value
            ]);
        } catch (Exception $e) {
            Log::error('CRMCoreSettings getSetting failed: ' . $e->getMessage());
            return Error::response(__('Failed to get setting value.'));
        }
    }
}