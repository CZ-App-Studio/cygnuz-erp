<?php

namespace Modules\AiChat\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AiChat\Services\GptService;
use Modules\AiChat\Services\SchemaService;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Services\AIProviderService;
use Modules\AICore\Services\AIRequestService;
use Modules\AICore\Services\AIUsageTracker;

class AiChatController extends Controller
{
    protected GptService $gptService;

    protected SchemaService $schemaService;

    protected ?AIRequestService $aiRequestService;

    protected ?AIUsageTracker $usageTracker;

    protected ?AIProviderService $providerService;

    public function __construct(
        GptService $gptService,
        SchemaService $schemaService
    ) {
        $this->gptService = $gptService;
        $this->schemaService = $schemaService;

        // Initialize AICore services if available
        if (class_exists('\Modules\AICore\Services\AIRequestService')) {
            $this->aiRequestService = app(AIRequestService::class);
            $this->usageTracker = app(AIUsageTracker::class);
            $this->providerService = app(AIProviderService::class);
        }
    }

    /**
     * Chat completion endpoint using AICore
     */
    public function chat(Request $request): JsonResponse
    {
        if (! $this->aiRequestService) {
            return response()->json([
                'success' => false,
                'message' => 'AI Core module is not available',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:10000',
            'context' => 'array',
            'provider' => 'string|in:openai,claude,gemini',
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
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => 'AiChat',
                'max_tokens' => $request->input('max_tokens', 2048),
                'temperature' => $request->input('temperature', 0.7),
                'provider_type' => $request->input('provider'),
            ];

            $result = $this->aiRequestService->chat(
                $request->input('message'),
                $request->input('context', []),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Chat request failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI request failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Text completion endpoint
     */
    public function complete(Request $request): JsonResponse
    {
        if (! $this->aiRequestService) {
            return response()->json([
                'success' => false,
                'message' => 'AI Core module is not available',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:10000',
            'provider' => 'string|in:openai,claude,gemini',
            'max_tokens' => 'integer|min:1|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => 'AiChat',
                'max_tokens' => $request->input('max_tokens', 2048),
                'provider_type' => $request->input('provider'),
            ];

            $result = $this->aiRequestService->complete(
                $request->input('prompt'),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI request failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Text summarization endpoint
     */
    public function summarize(Request $request): JsonResponse
    {
        if (! $this->aiRequestService) {
            return response()->json([
                'success' => false,
                'message' => 'AI Core module is not available',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000',
            'max_length' => 'integer|min:50|max:1000',
            'style' => 'in:concise,detailed,bullet_points,executive',
            'provider' => 'string|in:openai,claude,gemini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => 'AiChat',
                'max_length' => $request->input('max_length', 200),
                'style' => $request->input('style', 'concise'),
                'provider_type' => $request->input('provider'),
            ];

            $result = $this->aiRequestService->summarize(
                $request->input('text'),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Summarization failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Data extraction endpoint
     */
    public function extract(Request $request): JsonResponse
    {
        if (! $this->aiRequestService) {
            return response()->json([
                'success' => false,
                'message' => 'AI Core module is not available',
            ], 503);
        }

        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000',
            'fields' => 'required|array|min:1',
            'fields.*' => 'string',
            'provider' => 'string|in:openai,claude,gemini',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => 'AiChat',
                'provider_type' => $request->input('provider'),
            ];

            $result = $this->aiRequestService->extract(
                $request->input('text'),
                $request->input('fields'),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data extraction failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get usage statistics
     */
    public function usage(Request $request): JsonResponse
    {
        if (! $this->usageTracker) {
            return response()->json([
                'success' => false,
                'message' => 'Usage tracking is not available',
            ], 503);
        }

        try {
            $companyId = auth()->user()->currentCompany->id ?? null;

            if (! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company context required',
                ], 400);
            }

            $period = $request->input('period', 'daily');
            $usage = $this->usageTracker->getCurrentUsage($companyId, 'AiChat', $period);

            return response()->json([
                'success' => true,
                'data' => $usage,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get usage data: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available AI providers
     */
    public function getProviders(Request $request): JsonResponse
    {
        try {
            $providers = AIProvider::where('is_active', true)
                ->with('models')
                ->get()
                ->map(function ($provider) {
                    return [
                        'id' => $provider->id,
                        'name' => $provider->name,
                        'type' => $provider->type,
                        'models' => $provider->models->map(function ($model) {
                            return [
                                'id' => $model->id,
                                'name' => $model->name,
                                'identifier' => $model->model_identifier,
                                'capabilities' => $model->capabilities,
                                'max_tokens' => $model->max_tokens,
                            ];
                        }),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $providers,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get providers: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test provider connection
     */
    public function testProvider(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'provider_id' => 'required|exists:ai_providers,id',
            'test_message' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $provider = AIProvider::findOrFail($request->input('provider_id'));
            $testMessage = $request->input('test_message', 'Hello, this is a test message. Please respond with OK.');

            // Test based on provider type
            $success = false;
            $response = '';

            if ($provider->type === 'gemini' && class_exists('\Modules\GeminiAIProvider\Services\GeminiProviderService')) {
                $geminiService = new \Modules\GeminiAIProvider\Services\GeminiProviderService;
                $geminiService->setApiKey($provider->decrypted_api_key);
                $success = $geminiService->testConnection();
                $response = $success ? 'Gemini provider connected successfully' : 'Failed to connect to Gemini';
            } elseif ($this->aiRequestService) {
                // Use AICore service for testing
                $result = $this->aiRequestService->chat(
                    $testMessage,
                    [],
                    [
                        'company_id' => auth()->user()->currentCompany->id ?? null,
                        'module_name' => 'AiChat',
                        'provider_type' => $provider->type,
                        'max_tokens' => 50,
                    ]
                );
                $success = ! empty($result['response']);
                $response = $result['response'] ?? 'No response received';
            }

            return response()->json([
                'success' => $success,
                'message' => $success ? 'Provider connection successful' : 'Provider connection failed',
                'response' => $response,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle query - legacy method for GPT service
     */
    public function handleQuery(Request $request): JsonResponse
    {
        try {
            $userQuery = $request->input('query');

            // Try to use AICore if available with Gemini
            if ($this->aiRequestService) {
                $result = $this->aiRequestService->chat(
                    $userQuery,
                    [],
                    [
                        'company_id' => auth()->user()->currentCompany->id ?? null,
                        'module_name' => 'AiChat',
                        'provider_type' => 'gemini', // Use Gemini by default for queries
                    ]
                );

                return response()->json([
                    'success' => true,
                    'response' => $result['response'] ?? $result,
                ]);
            }

            // Fallback to original GPT service
            $response = $this->gptService->interpretQueryV2($userQuery);

            return response()->json([
                'success' => true,
                'response' => $response,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'response' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test endpoint
     */
    public function test(): JsonResponse
    {
        $result = DB::select('SELECT * FROM users LIMIT 5');

        return response()->json([
            'success' => true,
            'response' => $result,
            'aicore_available' => $this->aiRequestService !== null,
            'gemini_available' => class_exists('\Modules\GeminiAIProvider\Services\GeminiProviderService'),
        ]);
    }

    /**
     * Get database schema
     */
    public function getSchema(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'response' => $this->schemaService->getSchema(),
        ]);
    }
}
