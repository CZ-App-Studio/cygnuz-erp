<?php

namespace App\Services\Settings;

use App\Contracts\Settings\ModuleSettingsHandlerInterface;
use Illuminate\Support\Facades\Validator;

abstract class BaseModuleSettings implements ModuleSettingsHandlerInterface
{
    protected string $module;

    protected array $settings = [];

    protected ModuleSettingsService $settingsService;

    public function __construct()
    {
        $this->settingsService = app(ModuleSettingsService::class);
        $this->settings = $this->define();
    }

    /**
     * Define module settings
     */
    abstract protected function define(): array;

    /**
     * Get the settings definition for the module
     */
    public function getSettingsDefinition(): array
    {
        return $this->settings;
    }

    /**
     * Get the view path for module settings
     */
    public function getSettingsView(): string
    {
        return 'settings.modules._template';
    }

    /**
     * Validate settings data
     */
    public function validateSettings(array $data): array
    {
        $rules = [];
        $messages = [];

        foreach ($this->settings as $section => $items) {
            foreach ($items as $key => $config) {
                if (isset($config['validation'])) {
                    $rules[$key] = $config['validation'];
                }

                if (isset($config['validation_messages'])) {
                    foreach ($config['validation_messages'] as $rule => $message) {
                        $messages["{$key}.{$rule}"] = $message;
                    }
                }
            }
        }

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return [
            'valid' => true,
            'data' => $validator->validated(),
        ];
    }

    /**
     * Save settings data
     */
    public function saveSettings(array $data): bool
    {
        $validation = $this->validateSettings($data);

        if (! $validation['valid']) {
            return false;
        }

        foreach ($validation['data'] as $key => $value) {
            $this->settingsService->set($this->module, $key, $value);
        }

        return true;
    }

    /**
     * Get required permissions
     */
    public function getSettingsPermissions(): array
    {
        return ["manage-{$this->module}-settings"];
    }

    /**
     * Get current values
     */
    public function getCurrentValues(): array
    {
        $values = [];

        foreach ($this->settings as $section => $items) {
            foreach ($items as $key => $config) {
                $values[$key] = $this->settingsService->get(
                    $this->module,
                    $key,
                    $config['default'] ?? null
                );
            }
        }

        return $values;
    }

    /**
     * Get default values for all settings
     */
    public function getDefaultValues(): array
    {
        $defaults = [];

        foreach ($this->settings as $section => $items) {
            foreach ($items as $key => $config) {
                $defaults[$key] = $config['default'] ?? null;
            }
        }

        return $defaults;
    }
}
