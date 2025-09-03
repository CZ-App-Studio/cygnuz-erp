<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIModuleConfiguration;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Services\AIModuleDetectionService;

class AIModuleConfigurationController extends Controller
{
    protected AIModuleDetectionService $detectionService;

    public function __construct(AIModuleDetectionService $detectionService)
    {
        $this->detectionService = $detectionService;
    }

    /**
     * Display module configuration page
     */
    public function index(Request $request)
    {
        // Sync detected modules first
        $this->detectionService->syncModulesToDatabase();

        // Get all module configurations
        $configurations = AIModuleConfiguration::with(['defaultProvider', 'defaultModel'])
            ->ordered()
            ->get();

        // Get all active providers and models for dropdowns
        $providers = AIProvider::active()->orderBy('name')->get();
        $models = AIModel::with('provider')->active()->get();

        // Group models by provider for better UI
        $modelsByProvider = $models->groupBy('provider_id');

        return view('aicore::module-configuration.index', compact(
            'configurations',
            'providers',
            'models',
            'modelsByProvider'
        ));
    }

    /**
     * Update module configuration
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'default_provider_id' => 'nullable|exists:ai_providers,id',
            'default_model_id' => 'nullable|exists:ai_models,id',
            'max_tokens_limit' => 'required|integer|min:1|max:32000',
            'temperature_default' => 'required|numeric|min:0|max:2',
            'streaming_enabled' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $configuration = AIModuleConfiguration::findOrFail($id);

            $configuration->update([
                'default_provider_id' => $request->input('default_provider_id'),
                'default_model_id' => $request->input('default_model_id'),
                'max_tokens_limit' => $request->input('max_tokens_limit'),
                'temperature_default' => $request->input('temperature_default'),
                'streaming_enabled' => $request->has('streaming_enabled'),
                'is_active' => $request->has('is_active'),
            ]);

            return redirect()->route('aicore.module-configuration.index')
                ->with('success', 'Module configuration updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update module configuration', [
                'error' => $e->getMessage(),
                'module_id' => $id,
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update configuration: '.$e->getMessage());
        }
    }

    /**
     * Update module configuration via AJAX
     */
    public function updateAjax(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'default_provider_id' => 'nullable|exists:ai_providers,id',
            'default_model_id' => 'nullable|exists:ai_models,id',
            'max_tokens_limit' => 'nullable|integer|min:1|max:32000',
            'temperature_default' => 'nullable|numeric|min:0|max:2',
            'streaming_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $configuration = AIModuleConfiguration::findOrFail($id);

            // Update only provided fields
            $updateData = [];

            if ($request->has('default_provider_id')) {
                $updateData['default_provider_id'] = $request->input('default_provider_id');
            }

            if ($request->has('default_model_id')) {
                $updateData['default_model_id'] = $request->input('default_model_id');
            }

            if ($request->has('max_tokens_limit')) {
                $updateData['max_tokens_limit'] = $request->input('max_tokens_limit');
            }

            if ($request->has('temperature_default')) {
                $updateData['temperature_default'] = $request->input('temperature_default');
            }

            if ($request->has('streaming_enabled')) {
                $updateData['streaming_enabled'] = $request->input('streaming_enabled');
            }

            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->input('is_active');
            }

            $configuration->update($updateData);

            // Reload with relationships
            $configuration->load(['defaultProvider', 'defaultModel']);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'data' => $configuration,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update module configuration via AJAX', [
                'error' => $e->getMessage(),
                'module_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync AI modules from system
     */
    public function syncModules(Request $request)
    {
        try {
            $synced = $this->detectionService->syncModulesToDatabase();

            $message = count($synced) > 0
                ? 'Synced '.count($synced).' new AI modules: '.implode(', ', $synced)
                : 'All AI modules are already synced';

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'synced' => $synced,
                ]);
            }

            return redirect()->route('aicore.module-configuration.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Failed to sync AI modules', [
                'error' => $e->getMessage(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to sync modules: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to sync modules: '.$e->getMessage());
        }
    }

    /**
     * Get models for a specific provider (AJAX)
     */
    public function getProviderModels(Request $request, $providerId)
    {
        try {
            $models = AIModel::where('provider_id', $providerId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'model_identifier', 'type', 'max_tokens']);

            return response()->json([
                'success' => true,
                'models' => $models,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get models: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle module active status
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $configuration = AIModuleConfiguration::findOrFail($id);
            $configuration->is_active = ! $configuration->is_active;
            $configuration->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_active' => $configuration->is_active,
                    'message' => $configuration->is_active ? 'Module activated' : 'Module deactivated',
                ]);
            }

            return redirect()->route('aicore.module-configuration.index')
                ->with('success', $configuration->is_active ? 'Module activated' : 'Module deactivated');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to toggle status: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Failed to toggle status: '.$e->getMessage());
        }
    }
}
