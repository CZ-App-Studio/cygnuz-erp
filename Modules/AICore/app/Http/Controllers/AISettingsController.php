<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Responses\Error;
use App\Responses\Success;
use App\Services\Settings\ModuleSettingsService;
use Illuminate\Http\Request;
use Modules\AICore\app\Settings\AICoreSettings;

class AISettingsController extends Controller
{
    protected ModuleSettingsService $settingsService;

    protected AICoreSettings $aicoreSettings;

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->aicoreSettings = new AICoreSettings;
    }

    /**
     * Display the settings form
     */
    public function index()
    {
        return view('aicore::settings.index');
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        try {
            $settings = $this->aicoreSettings->getSettingsDefinition();

            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    if ($request->has($key)) {
                        $value = $request->input($key);

                        // Handle checkbox values
                        if ($config['type'] === 'toggle') {
                            $value = $value == '1' || $value == 'true' || $value == true;
                        }

                        // Validate if validation rules exist
                        if (isset($config['validation'])) {
                            $validator = validator([$key => $value], [$key => $config['validation']]);
                            if ($validator->fails()) {
                                return Error::response([
                                    'message' => __('Validation failed'),
                                    'errors' => $validator->errors(),
                                ]);
                            }
                        }

                        // Save the setting
                        $this->settingsService->set('AICore', "aicore.{$key}", $value);
                    }
                }
            }

            return Success::response([
                'message' => __('Settings updated successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to update settings: ').$e->getMessage());
        }
    }

    /**
     * Reset settings to defaults
     */
    public function reset()
    {
        try {
            $settings = $this->aicoreSettings->getSettingsDefinition();

            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $config) {
                    $defaultValue = $config['default'] ?? null;
                    $this->settingsService->set('AICore', "aicore.{$key}", $defaultValue);
                }
            }

            return Success::response([
                'message' => __('Settings reset to defaults successfully'),
            ]);

        } catch (\Exception $e) {
            return Error::response(__('Failed to reset settings: ').$e->getMessage());
        }
    }
}
