<?php

namespace Modules\AICore\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIModuleConfiguration;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Models\AIRequestLog;

class AIRequestService
{
    protected Client $httpClient;

    protected AIProviderService $providerService;

    protected AIUsageTracker $usageTracker;

    public function __construct(
        AIProviderService $providerService,
        AIUsageTracker $usageTracker
    ) {
        $timeout = setting('aicore.request_timeout', 30);
        $this->httpClient = new Client(['timeout' => $timeout]);
        $this->providerService = $providerService;
        $this->usageTracker = $usageTracker;
    }

    /**
     * Send a chat completion request
     */
    public function chat(string $message, array $context = [], array $options = []): array
    {
        // Check if AI is enabled globally
        if (! setting('aicore.ai_enabled', true)) {
            throw new \Exception('AI functionality is currently disabled. Please contact your administrator.');
        }

        // Check cache if enabled
        $cacheKey = null;
        if (setting('aicore.cache_enabled', false)) {
            $cacheKey = 'ai_response:'.md5(json_encode([
                'message' => $message,
                'context' => $context,
                'options' => $options,
            ]));

            $cachedResponse = Cache::get($cacheKey);
            if ($cachedResponse) {
                return $cachedResponse;
            }
        }

        // First check if there's a module-specific configuration
        $moduleName = $options['module_name'] ?? 'AICore';
        $model = null;

        // Try to get module-specific model configuration
        $moduleConfig = AIModuleConfiguration::where('module_name', $moduleName)
            ->where('is_active', true)
            ->first();

        if ($moduleConfig && $moduleConfig->default_model_id) {
            // Use module-specific model if configured
            $model = AIModel::with('provider')
                ->where('id', $moduleConfig->default_model_id)
                ->where('is_active', true)
                ->first();

            // Apply module-specific settings to options
            if ($moduleConfig->max_tokens_limit) {
                $options['max_tokens'] = $options['max_tokens'] ?? $moduleConfig->max_tokens_limit;
            }
            if ($moduleConfig->temperature_default) {
                $options['temperature'] = $options['temperature'] ?? $moduleConfig->temperature_default;
            }
            if ($moduleConfig->streaming_enabled) {
                $options['streaming'] = $options['streaming'] ?? $moduleConfig->streaming_enabled;
            }
        }

        // If no module-specific model or module config not found, fall back to auto-selection
        if (! $model) {
            $providerType = $options['provider_type'] ?? null;

            // If module config has a default provider, use it
            if ($moduleConfig && $moduleConfig->default_provider_id) {
                $provider = AIProvider::find($moduleConfig->default_provider_id);
                if ($provider) {
                    $providerType = $provider->type;
                }
            }

            $model = $this->providerService->getBestModelForTask('text', $options['max_tokens'] ?? null, $providerType);
        }

        if (! $model) {
            $errorMsg = isset($providerType)
                ? "No available AI model for provider type: {$providerType}"
                : "No available AI model for module: {$moduleName}";
            throw new \Exception($errorMsg);
        }

        $startTime = microtime(true);

        // Create request log entry only if logging is enabled
        $requestLog = null;
        if (setting('aicore.log_requests', true)) {
            $requestLog = AIRequestLog::create([
                'user_id' => auth()->id(),
                'module_name' => $moduleName,
                'operation_type' => $options['operation_type'] ?? 'chat',
                'model_id' => $model->id,
                'provider_name' => $model->provider->name,
                'model_name' => $model->name,
                'request_prompt' => $message,
                'request_options' => $options,
                'request_ip' => request()->ip(),
                'request_user_agent' => request()->userAgent(),
                'status' => 'pending',
            ]);
        }

        try {
            $response = $this->makeChatRequest($model, $message, $context, $options);
            $processingTime = (microtime(true) - $startTime) * 1000;

            $cost = $this->calculateCost($model, $response['usage'] ?? []);

            // Update request log with success response if logging is enabled
            if ($requestLog) {
                $requestLog->update([
                    'response_content' => $response['content'],
                    'response_metadata' => [
                        'model' => $model->name,
                        'provider' => $model->provider->name,
                    ],
                    'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                    'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                    'total_tokens' => $response['usage']['total_tokens'] ?? 0,
                    'cost' => $cost,
                    'processing_time_ms' => $processingTime,
                    'status' => 'success',
                ]);
            }

            // Log usage independently to ensure it's always saved
            $this->logUsageIndependently([
                'user_id' => auth()->id(),
                'company_id' => $options['company_id'] ?? null,
                'module_name' => $options['module_name'] ?? 'AICore',
                'operation_type' => 'chat',
                'model_id' => $model->id,
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
                'cost' => $cost,
                'processing_time_ms' => $processingTime,
                'status' => 'success',
            ]);

            $result = [
                'success' => true,
                'response' => $response['content'],
                'model' => $model->name,
                'model_id' => $model->id,
                'usage' => array_merge($response['usage'] ?? [], [
                    'cost' => $this->calculateCost($model, $response['usage'] ?? []),
                    'processing_time_ms' => $processingTime,
                ]),
                'processing_time' => round($processingTime, 2),
            ];

            // Cache the response if caching is enabled
            if ($cacheKey && setting('aicore.cache_enabled', false)) {
                $cacheTtl = setting('aicore.cache_ttl', 3600); // Default 1 hour
                Cache::put($cacheKey, $result, $cacheTtl);
            }

            return $result;

        } catch (\Exception $e) {
            $processingTime = (microtime(true) - $startTime) * 1000;

            // Update request log with error if logging is enabled
            if ($requestLog) {
                $requestLog->update([
                    'processing_time_ms' => $processingTime,
                    'status' => 'error',
                    'error_message' => $e->getMessage(),
                    'error_code' => $e->getCode() ? (string) $e->getCode() : null,
                ]);
            }

            // Log the error for debugging
            Log::error('AI Request Failed - Logging Usage', [
                'module' => $options['module_name'] ?? 'AICore',
                'model_id' => $model->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            // Always log usage even for errors - with 0 tokens if we don't have usage data
            // We need to save this independently of any parent transaction
            $usageData = [
                'user_id' => auth()->id(),
                'company_id' => $options['company_id'] ?? null,
                'module_name' => $options['module_name'] ?? 'AICore',
                'operation_type' => 'chat',
                'model_id' => $model->id,
                'prompt_tokens' => 0,  // No tokens for errors
                'completion_tokens' => 0,
                'total_tokens' => 0,
                'cost' => 0,
                'processing_time_ms' => $processingTime,
                'status' => 'error',
                'error_message' => substr($e->getMessage(), 0, 500), // Limit error message length
            ];

            // Save usage log in a separate database connection to avoid transaction rollback
            try {
                // Use a fresh database connection to bypass any active transactions
                $this->logUsageIndependently($usageData);
                Log::info('Error usage logged successfully for module: '.($options['module_name'] ?? 'AICore'));
            } catch (\Exception $logException) {
                Log::error('Failed to log error usage', [
                    'usage_data' => $usageData,
                    'log_error' => $logException->getMessage(),
                    'module' => $options['module_name'] ?? 'AICore',
                ]);
            }

            throw $e;
        }
    }

    /**
     * Text completion request
     */
    public function complete(string $prompt, array $options = []): array
    {
        return $this->chat($prompt, [], $options);
    }

    /**
     * Summarize text
     */
    public function summarize(string $text, array $options = []): array
    {
        $maxLength = $options['max_length'] ?? 200;
        $style = $options['style'] ?? 'concise';

        $prompt = "Please summarize the following text in a {$style} style, limiting the summary to approximately {$maxLength} words:\n\n{$text}\n\nSummary:";

        return $this->complete($prompt, array_merge($options, [
            'module_name' => $options['module_name'] ?? 'DocumentSummarizerAI',
        ]));
    }

    /**
     * Extract data from text
     */
    public function extract(string $text, array $fields, array $options = []): array
    {
        $fieldsList = implode(', ', $fields);
        $prompt = "Extract the following information from the text and return it as JSON: {$fieldsList}\n\nText:\n{$text}\n\nExtracted data (JSON):";

        return $this->complete($prompt, array_merge($options, [
            'module_name' => $options['module_name'] ?? 'PDFAnalyzerAI',
        ]));
    }

    /**
     * Make chat request based on provider type
     */
    protected function makeChatRequest(AIModel $model, string $message, array $context, array $options): array
    {
        switch ($model->provider->type) {
            case 'openai':
                return $this->makeOpenAIChatRequest($model, $message, $context, $options);
            case 'claude':
                return $this->makeClaudeChatRequest($model, $message, $context, $options);
            case 'gemini':
                return $this->makeGeminiChatRequest($model, $message, $context, $options);
            default:
                throw new \Exception("Unsupported provider type: {$model->provider->type}");
        }
    }

    /**
     * OpenAI chat request
     */
    protected function makeOpenAIChatRequest(AIModel $model, string $message, array $context, array $options): array
    {
        $messages = [];

        foreach ($context as $ctx) {
            $messages[] = [
                'role' => $ctx['role'] ?? 'user',
                'content' => $ctx['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer '.$model->provider->decrypted_api_key,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => $model->model_identifier,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? min($model->max_tokens, setting('aicore.default_max_tokens', 1000)),
                'temperature' => $options['temperature'] ?? setting('aicore.default_temperature', 0.7),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'content' => $data['choices'][0]['message']['content'] ?? '',
            'usage' => $data['usage'] ?? [],
        ];
    }

    /**
     * Claude chat request
     */
    protected function makeClaudeChatRequest(AIModel $model, string $message, array $context, array $options): array
    {
        $messages = [];

        foreach ($context as $ctx) {
            $messages[] = [
                'role' => $ctx['role'] ?? 'user',
                'content' => $ctx['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $model->provider->decrypted_api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
            'json' => [
                'model' => $model->model_identifier,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? min($model->max_tokens, setting('aicore.default_max_tokens', 1000)),
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'usage' => [
                'prompt_tokens' => $data['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $data['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            ],
        ];
    }

    /**
     * Gemini chat request
     */
    protected function makeGeminiChatRequest(AIModel $model, string $message, array $context, array $options): array
    {
        $messages = [];

        foreach ($context as $ctx) {
            $messages[] = [
                'role' => $ctx['role'] ?? 'user',
                'content' => $ctx['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        // Log the request details for debugging
        Log::info('Making Gemini chat request', [
            'model' => $model->model_identifier,
            'provider' => $model->provider->name,
            'has_api_key' => ! empty($model->provider->decrypted_api_key),
            'message_count' => count($messages),
        ]);

        // Use Gemini service if available
        if (class_exists('\Modules\GeminiAIProvider\Services\GeminiProviderService')) {
            try {
                $geminiService = new \Modules\GeminiAIProvider\Services\GeminiProviderService;
                $apiKey = $model->provider->decrypted_api_key;

                if (empty($apiKey)) {
                    throw new \Exception('No API key configured for Gemini provider');
                }

                $geminiService->setApiKey($apiKey);

                $response = $geminiService->chat(
                    $model->model_identifier,
                    $messages,
                    [
                        'max_tokens' => $options['max_tokens'] ?? min($model->max_tokens, setting('aicore.default_max_tokens', 1000)),
                        'temperature' => $options['temperature'] ?? setting('aicore.default_temperature', 0.7),
                    ]
                );

                return $response;
            } catch (\Exception $e) {
                Log::error('Gemini service error', [
                    'error' => $e->getMessage(),
                    'model' => $model->model_identifier,
                ]);
                // Fall through to direct API call
            }
        }

        // Fallback to direct API call
        $contents = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $apiKey = $model->provider->decrypted_api_key;
        if (empty($apiKey)) {
            throw new \Exception('No API key configured for Gemini provider');
        }

        try {
            $response = $this->httpClient->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model->model_identifier}:generateContent",
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'query' => [
                        'key' => $apiKey,
                    ],
                    'json' => [
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => $options['temperature'] ?? setting('aicore.default_temperature', 0.7),
                            'maxOutputTokens' => $options['max_tokens'] ?? min($model->max_tokens, setting('aicore.default_max_tokens', 1000)),
                            'topP' => 0.95,
                            'topK' => 40,
                        ],
                    ],
                ]
            );

            $data = json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $responseBody = $e->getResponse()->getBody()->getContents();
            Log::error('Gemini API error', [
                'status' => $e->getResponse()->getStatusCode(),
                'response' => $responseBody,
                'model' => $model->model_identifier,
            ]);

            $errorData = json_decode($responseBody, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown Gemini API error';

            if ($e->getResponse()->getStatusCode() === 401) {
                throw new \Exception('Unauthorized: Please check your Gemini API key configuration');
            }

            throw new \Exception('Gemini API error: '.$errorMessage);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Handle 5xx server errors (like 503)
            $responseBody = $e->getResponse()->getBody()->getContents();
            Log::error('Gemini API server error', [
                'status' => $e->getResponse()->getStatusCode(),
                'response' => $responseBody,
                'model' => $model->model_identifier,
            ]);

            $errorData = json_decode($responseBody, true);
            $errorMessage = $errorData['error']['message'] ?? 'Gemini service unavailable';

            throw new \Exception('Gemini API error: '.$errorMessage);
        }

        $content = '';
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $data['candidates'][0]['content']['parts'][0]['text'];
        }

        return [
            'content' => $content,
            'usage' => [
                'prompt_tokens' => $data['usageMetadata']['promptTokenCount'] ?? 0,
                'completion_tokens' => $data['usageMetadata']['candidatesTokenCount'] ?? 0,
                'total_tokens' => ($data['usageMetadata']['promptTokenCount'] ?? 0) + ($data['usageMetadata']['candidatesTokenCount'] ?? 0),
            ],
        ];
    }

    /**
     * Log usage independently of any active transaction
     * This ensures usage is always logged even if parent transaction rolls back
     */
    protected function logUsageIndependently(array $data): void
    {
        // Skip straight to PDO connection to bypass any Laravel database transaction handling
        try {
            // Create a completely separate PDO connection that's independent of Laravel's DB facade
            $config = config('database.connections.'.config('database.default'));
            $pdo = new \PDO(
                "mysql:host={$config['host']};dbname={$config['database']};port={$config['port']}",
                $config['username'],
                $config['password'],
                [\PDO::ATTR_AUTOCOMMIT => true] // Ensure autocommit is on
            );

            $stmt = $pdo->prepare('INSERT INTO ai_usage_logs (
                user_id, company_id, module_name, operation_type, model_id, 
                prompt_tokens, completion_tokens, total_tokens, cost, 
                processing_time_ms, status, error_message, request_hash, 
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            $timestamp = date('Y-m-d H:i:s');
            $success = $stmt->execute([
                $data['user_id'] ?? null,
                $data['company_id'] ?? null,
                $data['module_name'],
                $data['operation_type'],
                $data['model_id'],
                $data['prompt_tokens'] ?? 0,
                $data['completion_tokens'] ?? 0,
                $data['total_tokens'] ?? 0,
                $data['cost'] ?? 0,
                $data['processing_time_ms'] ?? null,
                $data['status'] ?? 'success',
                $data['error_message'] ?? null,
                $data['request_hash'] ?? null,
                $timestamp,
                $timestamp,
            ]);

            if (! $success) {
                $errorInfo = $stmt->errorInfo();
                throw new \Exception('PDO insert failed: '.json_encode($errorInfo));
            }

            Log::info('Usage logged using separate PDO connection', [
                'module' => $data['module_name'],
                'status' => $data['status'],
                'error' => $data['error_message'] ?? null,
                'inserted_id' => $pdo->lastInsertId(),
            ]);
        } catch (\Exception $pdoError) {
            Log::error('Failed to log usage with PDO', [
                'error' => $pdoError->getMessage(),
                'data' => $data,
            ]);

            // Try one more time with a completely fresh connection and explicit commit
            try {
                $config = config('database.connections.'.config('database.default'));
                $dsn = "mysql:host={$config['host']};dbname={$config['database']};port={$config['port']};charset=utf8mb4";
                $pdo = new \PDO($dsn, $config['username'], $config['password']);
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);

                // Start our own transaction that we control
                $pdo->beginTransaction();

                $sql = 'INSERT INTO ai_usage_logs (
                    user_id, company_id, module_name, operation_type, model_id, 
                    prompt_tokens, completion_tokens, total_tokens, cost, 
                    processing_time_ms, status, error_message, request_hash, 
                    created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

                $stmt = $pdo->prepare($sql);
                $timestamp = date('Y-m-d H:i:s');

                $stmt->execute([
                    $data['user_id'] ?? null,
                    $data['company_id'] ?? null,
                    $data['module_name'],
                    $data['operation_type'],
                    $data['model_id'],
                    $data['prompt_tokens'] ?? 0,
                    $data['completion_tokens'] ?? 0,
                    $data['total_tokens'] ?? 0,
                    $data['cost'] ?? 0,
                    $data['processing_time_ms'] ?? null,
                    $data['status'] ?? 'success',
                    $data['error_message'] ?? null,
                    $data['request_hash'] ?? null,
                    $timestamp,
                    $timestamp,
                ]);

                // Explicitly commit our transaction
                $pdo->commit();

                Log::info('Usage logged with explicit PDO transaction', [
                    'module' => $data['module_name'],
                    'status' => $data['status'],
                    'inserted_id' => $pdo->lastInsertId(),
                ]);

                // Close the connection
                $pdo = null;
            } catch (\Exception $finalError) {
                Log::error('Final attempt to log usage failed', [
                    'error' => $finalError->getMessage(),
                    'trace' => $finalError->getTraceAsString(),
                    'data' => $data,
                ]);
            }
        }
    }

    /**
     * Calculate cost based on usage
     */
    protected function calculateCost(AIModel $model, array $usage): float
    {
        $inputTokens = $usage['prompt_tokens'] ?? 0;
        $outputTokens = $usage['completion_tokens'] ?? 0;

        return $model->calculateCost($inputTokens, $outputTokens);
    }

    /**
     * Get the configured model for a specific module
     */
    public function getModuleModel(string $moduleName): ?AIModel
    {
        $moduleConfig = AIModuleConfiguration::where('module_name', $moduleName)
            ->where('is_active', true)
            ->first();

        if (! $moduleConfig) {
            return null;
        }

        if ($moduleConfig->default_model_id) {
            return AIModel::with('provider')
                ->where('id', $moduleConfig->default_model_id)
                ->where('is_active', true)
                ->first();
        }

        if ($moduleConfig->default_provider_id) {
            $provider = AIProvider::find($moduleConfig->default_provider_id);
            if ($provider) {
                return $this->providerService->getBestModelForTask('text', null, $provider->type);
            }
        }

        return null;
    }

    /**
     * Get module configuration with defaults
     */
    public function getModuleConfiguration(string $moduleName): array
    {
        $moduleConfig = AIModuleConfiguration::where('module_name', $moduleName)
            ->where('is_active', true)
            ->first();

        if (! $moduleConfig) {
            return [
                'max_tokens' => 2048,
                'temperature' => 0.7,
                'streaming' => false,
                'model' => null,
                'provider' => null,
            ];
        }

        return [
            'max_tokens' => $moduleConfig->max_tokens_limit ?? 2048,
            'temperature' => $moduleConfig->temperature_default ?? 0.7,
            'streaming' => $moduleConfig->streaming_enabled ?? false,
            'model' => $moduleConfig->defaultModel,
            'provider' => $moduleConfig->defaultProvider,
        ];
    }
}
