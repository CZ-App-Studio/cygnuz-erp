<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Services\AIProviderService;
use Modules\AICore\Services\AIProviderAddonService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AIProviderController extends Controller
{
    protected AIProviderService $providerService;
    protected AIProviderAddonService $providerAddonService;

    public function __construct(AIProviderService $providerService, AIProviderAddonService $providerAddonService)
    {
        $this->providerService = $providerService;
        $this->providerAddonService = $providerAddonService;
    }

    /**
     * Display a listing of AI providers
     */
    public function index()
    {
        $providers = $this->providerAddonService->getEnabledProviders()->load(['models' => function($query) {
            $query->active();
        }]);
        
        $availableAddons = $this->providerAddonService->getAvailableProviderAddons();
        $disabledAddons = $this->providerAddonService->getDisabledProviderAddons();

        return view('aicore::providers.index', compact('providers', 'availableAddons', 'disabledAddons'));
    }

    /**
     * Show the form for creating a new provider
     */
    public function create()
    {
        $enabledTypes = $this->providerAddonService->getEnabledProviderTypes();
        
        $providerTypes = [];
        foreach ($enabledTypes as $type) {
            $providerTypes[$type] = $this->getProviderTypeName($type);
        }

        return view('aicore::providers.create', compact('providerTypes'));
    }

    /**
     * Store a newly created provider
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:openai,claude,gemini,local,custom',
            'api_key' => 'required_unless:type,local|string',
            'endpoint_url' => 'nullable|url',
            'max_requests_per_minute' => 'nullable|integer|min:1|max:1000',
            'max_tokens_per_request' => 'nullable|integer|min:1|max:32000',
            'cost_per_token' => 'nullable|numeric|min:0',
            'priority' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Create the provider
            $data = $request->all();
            
            // Set default values if not provided
            $data['max_requests_per_minute'] = $data['max_requests_per_minute'] ?? 60;
            $data['max_tokens_per_request'] = $data['max_tokens_per_request'] ?? 4000;
            $data['cost_per_token'] = $data['cost_per_token'] ?? 0.000015;
            $data['priority'] = $data['priority'] ?? 1;
            $data['is_active'] = $request->has('is_active') ? ($request->input('is_active') == '1') : false;
            
            // Encrypt API key if provided
            if ($request->filled('api_key')) {
                $data['api_key_encrypted'] = \Illuminate\Support\Facades\Crypt::encryptString($request->input('api_key'));
            }
            
            // Remove plain api_key from data
            unset($data['api_key']);
            
            $provider = AIProvider::create($data);

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AI Provider created successfully',
                    'redirect' => route('aicore.providers.index'),
                    'provider' => $provider
                ]);
            }

            return redirect()->route('aicore.providers.index')
                ->with('success', 'AI Provider created successfully');

        } catch (\Exception $e) {
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create provider: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to create provider: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified provider
     */
    public function show(AIProvider $provider)
    {
        $provider->load(['models', 'usageLogs' => function($query) {
            $query->recent(30)->limit(100);
        }]);

        $usageStats = $this->providerService->getUsageStats($provider, 30);

        return view('aicore::providers.show', compact('provider', 'usageStats'));
    }

    /**
     * Show the form for editing the provider
     */
    public function edit(AIProvider $provider)
    {
        $enabledTypes = $this->providerAddonService->getEnabledProviderTypes();
        
        $providerTypes = [];
        foreach ($enabledTypes as $type) {
            $providerTypes[$type] = $this->getProviderTypeName($type);
        }

        return view('aicore::providers.edit', compact('provider', 'providerTypes'));
    }

    /**
     * Update the specified provider
     */
    public function update(Request $request, AIProvider $provider)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'type' => 'required|in:openai,claude,gemini,local,custom',
            'api_key' => 'nullable|string',
            'endpoint_url' => 'nullable|url',
            'max_requests_per_minute' => 'nullable|integer|min:1|max:1000',
            'max_tokens_per_request' => 'nullable|integer|min:1|max:32000',
            'cost_per_token' => 'nullable|numeric|min:0',
            'priority' => 'nullable|integer|min:1|max:100',
            'is_active' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $data = $request->all();
            
            // Set default values if not provided
            $data['max_requests_per_minute'] = $data['max_requests_per_minute'] ?? 60;
            $data['max_tokens_per_request'] = $data['max_tokens_per_request'] ?? 4000;
            $data['cost_per_token'] = $data['cost_per_token'] ?? 0.000015;
            $data['priority'] = $data['priority'] ?? 1;
            $data['is_active'] = $request->has('is_active') ? ($request->input('is_active') == '1') : false;
            
            // Handle API key update
            if ($request->filled('api_key')) {
                $data['api_key_encrypted'] = \Illuminate\Support\Facades\Crypt::encryptString($request->input('api_key'));
            }
            
            // Remove plain api_key from data
            unset($data['api_key']);
            
            $provider->update($data);

            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'AI Provider updated successfully',
                    'redirect' => route('aicore.providers.index'),
                    'provider' => $provider
                ]);
            }

            return redirect()->route('aicore.providers.index')
                ->with('success', 'AI Provider updated successfully');

        } catch (\Exception $e) {
            // Return JSON response for AJAX requests
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update provider: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to update provider: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified provider
     */
    public function destroy(AIProvider $provider)
    {
        try {
            // Check if provider has associated models
            if ($provider->models()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete provider with associated models');
            }

            $provider->delete();

            return redirect()->route('aicore.providers.index')
                ->with('success', 'AI Provider deleted successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete provider: ' . $e->getMessage());
        }
    }

    /**
     * Test connection to the provider
     */
    public function testConnection(Request $request, AIProvider $provider)
    {
        try {
            // Basic validation
            if (!$provider->api_key_encrypted && $provider->type !== 'local') {
                return response()->json([
                    'success' => false,
                    'message' => 'No API key configured for this provider',
                    'response_time' => null
                ]);
            }

            $startTime = microtime(true);
            $testSuccessful = false;
            $testMessage = '';
            
            // Test based on provider type
            switch ($provider->type) {
                case 'gemini':
                    // Use GeminiProviderService if available
                    if (class_exists('\Modules\GeminiAIProvider\Services\GeminiProviderService')) {
                        $service = new \Modules\GeminiAIProvider\Services\GeminiProviderService();
                        $apiKey = \Illuminate\Support\Facades\Crypt::decryptString($provider->api_key_encrypted);
                        $service->setApiKey($apiKey);
                        
                        // Get first active model or use default
                        $model = $provider->models()->where('is_active', true)->first();
                        $modelId = $model ? $model->model_identifier : 'gemini-1.5-flash';
                        
                        $testSuccessful = $service->testConnection($modelId);
                        $testMessage = $testSuccessful ? 'Gemini API connection successful' : 'Gemini API connection failed';
                    } else {
                        $testMessage = 'GeminiAIProvider module not installed';
                    }
                    break;
                    
                case 'openai':
                    // OpenAI test logic
                    try {
                        $apiKey = \Illuminate\Support\Facades\Crypt::decryptString($provider->api_key_encrypted);
                        $client = new \GuzzleHttp\Client(['timeout' => 10]);
                        
                        // Test OpenAI API by listing models
                        $response = $client->get('https://api.openai.com/v1/models', [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $apiKey
                            ]
                        ]);
                        
                        $data = json_decode($response->getBody()->getContents(), true);
                        
                        if (isset($data['data']) && is_array($data['data'])) {
                            $testSuccessful = true;
                            $testMessage = 'OpenAI API connection successful';
                        } else {
                            $testMessage = 'OpenAI API returned unexpected response';
                        }
                    } catch (\Exception $e) {
                        $testMessage = 'OpenAI API connection failed: ' . $e->getMessage();
                        \Log::error('OpenAI connection test failed', ['error' => $e->getMessage()]);
                    }
                    break;
                    
                case 'claude':
                    // Claude test logic
                    if (class_exists('\Modules\ClaudeAIProvider\Services\ClaudeProviderService')) {
                        try {
                            $service = new \Modules\ClaudeAIProvider\Services\ClaudeProviderService();
                            $apiKey = \Illuminate\Support\Facades\Crypt::decryptString($provider->api_key_encrypted);
                            $service->setApiKey($apiKey);
                            
                            // Test connection with a simple message
                            $testSuccessful = $service->testConnection();
                            $testMessage = $testSuccessful ? 'Claude API connection successful' : 'Claude API connection failed';
                        } catch (\Exception $e) {
                            $testMessage = 'Claude API connection failed: ' . $e->getMessage();
                            \Log::error('Claude connection test failed', ['error' => $e->getMessage()]);
                        }
                    } else {
                        // Fallback direct API test if Claude module not available
                        try {
                            $apiKey = \Illuminate\Support\Facades\Crypt::decryptString($provider->api_key_encrypted);
                            $client = new \GuzzleHttp\Client(['timeout' => 10]);
                            
                            // Test Claude API with a simple message
                            $response = $client->post('https://api.anthropic.com/v1/messages', [
                                'headers' => [
                                    'Content-Type' => 'application/json',
                                    'x-api-key' => $apiKey,
                                    'anthropic-version' => '2023-06-01'
                                ],
                                'json' => [
                                    'model' => 'claude-3-haiku-20240307',
                                    'messages' => [
                                        ['role' => 'user', 'content' => 'Reply with OK']
                                    ],
                                    'max_tokens' => 10
                                ]
                            ]);
                            
                            $data = json_decode($response->getBody()->getContents(), true);
                            
                            if (isset($data['content'])) {
                                $testSuccessful = true;
                                $testMessage = 'Claude API connection successful';
                            } else {
                                $testMessage = 'Claude API returned unexpected response';
                            }
                        } catch (\Exception $e) {
                            $testMessage = 'Claude API connection failed';
                            \Log::error('Claude connection test failed', ['error' => $e->getMessage()]);
                        }
                    }
                    break;
                    
                case 'local':
                    // Local model test logic
                    $testSuccessful = true;
                    $testMessage = 'Local provider ready';
                    break;
                    
                default:
                    $testMessage = 'Provider type not supported for testing';
                    break;
            }
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => $testSuccessful,
                'message' => $testMessage,
                'response_time' => $responseTime,
                'provider_info' => [
                    'provider_type' => $provider->type,
                    'endpoint' => $provider->endpoint_url
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'response_time' => null
            ], 500);
        }
    }

    /**
     * Get user-friendly provider type name
     */
    protected function getProviderTypeName(string $type): string
    {
        $names = [
            'openai' => 'OpenAI',
            'claude' => 'Claude (Anthropic)',
            'gemini' => 'Google Gemini',
            'azure-openai' => 'Azure OpenAI',
            'cohere' => 'Cohere',
            'huggingface' => 'Hugging Face',
            'mistral' => 'Mistral AI',
            'ollama' => 'Ollama (Local)',
            'bedrock' => 'AWS Bedrock',
            'palm' => 'Google PaLM'
        ];

        return $names[$type] ?? ucfirst($type);
    }
}