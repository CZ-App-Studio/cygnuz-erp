<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\Settings\ModuleSettingsService;
use App\Services\Settings\SettingsRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ModuleSettingsController extends Controller
{
    public function __construct(
        protected ModuleSettingsService $moduleSettings,
        protected SettingsRegistry $registry
    ) {}

    /**
     * Display module settings page
     */
    public function index(string $module): View
    {
        $moduleConfig = $this->registry->getModuleConfig($module);
        
        if (!$moduleConfig) {
            abort(404, __('Module settings not found'));
        }

        // Check permissions
        if (!empty($moduleConfig['permissions'])) {
            $this->authorize('any', $moduleConfig['permissions']);
        }

        // Get module handler
        $handlerClass = $moduleConfig['handler'];
        if (!class_exists($handlerClass)) {
            abort(500, __('Module settings handler not found'));
        }

        $moduleHandler = app($handlerClass);
        $settings = $moduleHandler->getSettingsDefinition();
        $values = $moduleHandler->getCurrentValues();
        $view = $moduleHandler->getSettingsView();

        return view($view, compact('module', 'moduleConfig', 'moduleHandler', 'settings', 'values'));
    }

    /**
     * Update module settings
     */
    public function update(Request $request, string $module): JsonResponse
    {
        $moduleConfig = $this->registry->getModuleConfig($module);
        
        if (!$moduleConfig) {
            return response()->json([
                'success' => false,
                'message' => __('Module settings not found')
            ], 404);
        }

        // Check permissions
        if (!empty($moduleConfig['permissions'])) {
            $this->authorize('any', $moduleConfig['permissions']);
        }

        try {
            DB::beginTransaction();

            // Get module handler
            $handlerClass = $moduleConfig['handler'];
            $handler = app($handlerClass);

            // Save settings through handler
            $data = $request->except(['_token', '_method']);
            
            if (!$handler->saveSettings($data)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Validation failed'),
                    'errors' => $handler->validateSettings($data)['errors'] ?? []
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Module settings updated successfully')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating module settings for {$module}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('Failed to update module settings')
            ], 500);
        }
    }

    /**
     * Get module settings form via AJAX
     */
    public function getModuleForm(string $module)
    {
        $moduleConfig = $this->registry->getModuleConfig($module);
        
        if (!$moduleConfig) {
            return response('<div class="alert alert-danger">Module settings not found</div>', 404);
        }

        // Check permissions
        if (!empty($moduleConfig['permissions'])) {
            try {
                $this->authorize('any', $moduleConfig['permissions']);
            } catch (\Exception $e) {
                return response('<div class="alert alert-danger">Access denied</div>', 403);
            }
        }

        // Get module handler
        $handlerClass = $moduleConfig['handler'];
        $handler = app($handlerClass);
        
        $settings = $handler->getSettingsDefinition();
        $values = $handler->getCurrentValues();

        return view('settings.modules._form', compact('module', 'moduleConfig', 'settings', 'values'));
    }

    /**
     * Reset module settings to defaults
     */
    public function reset(string $module): JsonResponse
    {
        $moduleConfig = $this->registry->getModuleConfig($module);
        
        if (!$moduleConfig) {
            return response()->json([
                'success' => false,
                'message' => __('Module settings not found')
            ], 404);
        }

        // Check permissions
        if (!empty($moduleConfig['permissions'])) {
            $this->authorize('any', $moduleConfig['permissions']);
        }

        try {
            DB::beginTransaction();

            // Get module handler
            $handlerClass = $moduleConfig['handler'];
            $handler = app($handlerClass);

            // Reset settings through handler
            $defaultValues = $handler->getDefaultValues();
            
            if (!$handler->saveSettings($defaultValues)) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to reset settings')
                ], 500);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Module settings reset to defaults successfully')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error resetting module settings for {$module}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('Failed to reset module settings')
            ], 500);
        }
    }
}