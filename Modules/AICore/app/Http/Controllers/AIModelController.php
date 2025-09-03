<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Services\AIProviderAddonService;

class AIModelController extends Controller
{
    protected AIProviderAddonService $providerAddonService;

    public function __construct(AIProviderAddonService $providerAddonService)
    {
        $this->providerAddonService = $providerAddonService;
    }

    /**
     * Display a listing of AI models
     */
    public function index(Request $request)
    {
        $query = AIModel::with('provider');

        // Apply filters if provided
        if ($request->filled('provider')) {
            $query->where('provider_id', $request->input('provider'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $isActive = $request->input('status') === 'active';
            $query->where('is_active', $isActive);
        }

        $models = $query->get();
        $providers = $this->providerAddonService->getActiveEnabledProviders();

        return view('aicore::models.index', compact('models', 'providers'));
    }

    /**
     * Show the form for creating a new model
     */
    public function create()
    {
        $providers = $this->providerAddonService->getActiveEnabledProviders();

        $modelTypes = [
            'text' => 'Text Generation',
            'image' => 'Image Generation',
            'embedding' => 'Text Embeddings',
            'multimodal' => 'Multimodal (Text + Image)',
        ];

        return view('aicore::models.create', compact('providers', 'modelTypes'));
    }

    /**
     * Store a newly created model
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|exists:ai_providers,id',
            'name' => 'required|string|max:100',
            'model_identifier' => 'required|string|max:200',
            'type' => 'required|in:text,image,embedding,multimodal',
            'max_tokens' => 'integer|min:1|max:32000',
            'supports_streaming' => 'boolean',
            'cost_per_input_token' => 'nullable|numeric|min:0',
            'cost_per_output_token' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->all();

            // Handle checkbox values
            $data['supports_streaming'] = $request->has('supports_streaming') ? ($request->input('supports_streaming') == '1') : false;
            $data['is_active'] = $request->has('is_active') ? ($request->input('is_active') == '1') : false;

            // Set default values for optional fields
            $data['max_tokens'] = $data['max_tokens'] ?? 4096;

            // Handle JSON configuration if provided
            if ($request->filled('configuration')) {
                try {
                    $data['configuration'] = json_decode($request->input('configuration'), true);
                } catch (\Exception $e) {
                    $data['configuration'] = null;
                }
            }

            AIModel::create($data);

            return redirect()->route('aicore.models.index')
                ->with('success', 'AI Model created successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to create model: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified model
     */
    public function show(AIModel $model)
    {
        $model->load('provider');

        return view('aicore::models.show', compact('model'));
    }

    /**
     * Show the form for editing the model
     */
    public function edit(AIModel $model)
    {
        $providers = $this->providerAddonService->getActiveEnabledProviders();

        $modelTypes = [
            'text' => 'Text Generation',
            'image' => 'Image Generation',
            'embedding' => 'Text Embeddings',
            'multimodal' => 'Multimodal (Text + Image)',
        ];

        return view('aicore::models.edit', compact('model', 'providers', 'modelTypes'));
    }

    /**
     * Update the specified model
     */
    public function update(Request $request, AIModel $model)
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|exists:ai_providers,id',
            'name' => 'required|string|max:100',
            'model_identifier' => 'required|string|max:200',
            'type' => 'required|in:text,image,embedding,multimodal',
            'max_tokens' => 'integer|min:1|max:32000',
            'supports_streaming' => 'boolean',
            'cost_per_input_token' => 'nullable|numeric|min:0',
            'cost_per_output_token' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->all();

            // Handle checkbox values
            $data['supports_streaming'] = $request->has('supports_streaming') ? ($request->input('supports_streaming') == '1') : false;
            $data['is_active'] = $request->has('is_active') ? ($request->input('is_active') == '1') : false;

            // Set default values for optional fields
            $data['max_tokens'] = $data['max_tokens'] ?? 4096;

            // Handle JSON configuration if provided
            if ($request->filled('configuration')) {
                try {
                    $data['configuration'] = json_decode($request->input('configuration'), true);
                } catch (\Exception $e) {
                    $data['configuration'] = null;
                }
            }

            $model->update($data);

            return redirect()->route('aicore.models.index')
                ->with('success', 'AI Model updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update model: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified model
     */
    public function destroy(AIModel $model)
    {
        try {
            $model->delete();

            return redirect()->route('aicore.models.index')
                ->with('success', 'AI Model deleted successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete model: '.$e->getMessage());
        }
    }

    /**
     * Test the AI model
     */
    public function test(Request $request, AIModel $model)
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:10000',
            'max_tokens' => 'integer|min:1|max:8192',
            'temperature' => 'numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            // Check if model and provider are active
            if (! $model->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Model is not active',
                ], 400);
            }

            if (! $model->provider || ! $model->provider->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider is not active',
                ], 400);
            }

            // Check if provider has API key
            if (! $model->provider->decrypted_api_key && $model->provider->type !== 'local') {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider API key is not configured',
                ], 400);
            }

            // Use AIRequestService to make the request
            $aiRequestService = app(\Modules\AICore\Services\AIRequestService::class);
            $usageTracker = app(\Modules\AICore\Services\AIUsageTracker::class);

            $options = [
                'company_id' => null, // Single company application - no need for company_id
                'module_name' => 'AICore_ModelTest', // Special module name for testing
                'max_tokens' => $request->input('max_tokens', 100),
                'temperature' => $request->input('temperature', 0.7),
                'provider_type' => $model->provider->type,
            ];

            $startTime = microtime(true);

            // Make the completion request - this will automatically log usage
            $result = $aiRequestService->complete(
                $request->input('prompt'),
                $options
            );

            // The usage is already logged by AIRequestService::chat method
            // which is called by complete()

            // Calculate cost
            $cost = $model->calculateCost(
                $result['usage']['prompt_tokens'] ?? 0,
                $result['usage']['completion_tokens'] ?? 0
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'response' => $result['response'] ?? '',
                    'model' => $model->name,
                    'provider' => $model->provider->name,
                    'usage' => [
                        'prompt_tokens' => $result['usage']['prompt_tokens'] ?? 0,
                        'completion_tokens' => $result['usage']['completion_tokens'] ?? 0,
                        'total_tokens' => $result['usage']['total_tokens'] ?? 0,
                    ],
                    'cost' => $cost,
                    'processing_time' => $result['processing_time'] ?? 0,
                    'usage_logged' => true, // Confirm that usage was logged
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Model test failed', [
                'model_id' => $model->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
