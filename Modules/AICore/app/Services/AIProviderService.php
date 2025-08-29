<?php

namespace Modules\AICore\Services;

use Modules\AICore\Models\AIProvider;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIUsageLog;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AIProviderService
{
    protected Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => config('ai.timeout', 30),
            'connect_timeout' => config('ai.connect_timeout', 10),
        ]);
    }

    /**
     * Get available provider for a specific task type
     */
    public function getAvailableProvider(string $taskType = 'text'): ?AIProvider
    {
        $providers = AIProvider::active()
            ->byPriority()
            ->get();

        foreach ($providers as $provider) {
            if ($this->isProviderAvailable($provider, $taskType)) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Get the best model for a specific task
     */
    public function getBestModelForTask(string $taskType, int $maxTokens = null, string $providerType = null): ?AIModel
    {
        $query = AIModel::with('provider')
            ->whereHas('provider', function ($q) use ($providerType) {
                $q->where('ai_providers.is_active', true);
                if ($providerType) {
                    $q->where('ai_providers.type', $providerType);
                }
            })
            ->where('ai_models.is_active', true)
            ->where('ai_models.type', $taskType);

        if ($maxTokens) {
            $query->where('ai_models.max_tokens', '>=', $maxTokens);
        }

        // Order by provider priority, then by cost efficiency
        return $query->join('ai_providers', 'ai_models.provider_id', '=', 'ai_providers.id')
            ->orderBy('ai_providers.priority')
            ->orderBy('ai_models.cost_per_input_token')
            ->select('ai_models.*')
            ->first();
    }

    /**
     * Test connection to a provider
     */
    public function testConnection(AIProvider $provider): array
    {
        try {
            $startTime = microtime(true);
            
            $response = $this->makeTestRequest($provider);
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'success' => true,
                'response_time' => $responseTime,
                'message' => 'Connection successful',
                'provider_info' => $response
            ];
        } catch (RequestException $e) {
            Log::error("AI Provider connection test failed", [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'response_time' => null,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'error_code' => $e->getCode()
            ];
        } catch (\Exception $e) {
            Log::error("AI Provider connection test error", [
                'provider_id' => $provider->id,
                'provider_type' => $provider->type,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'response_time' => null,
                'message' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get usage statistics for a provider
     */
    public function getUsageStats(AIProvider $provider, int $days = 30): array
    {
        // Get model IDs for this provider
        $modelIds = $provider->models()->pluck('id');
        
        // Query usage logs directly to avoid GROUP BY issues with hasManyThrough
        $stats = AIUsageLog::whereIn('model_id', $modelIds)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_requests,
                SUM(total_tokens) as total_tokens,
                SUM(cost) as total_cost,
                AVG(processing_time_ms) as avg_processing_time,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_requests
            ')
            ->first();

        return [
            'provider_id' => $provider->id,
            'provider_name' => $provider->name,
            'period_days' => $days,
            'total_requests' => $stats->total_requests ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => round($stats->total_cost ?? 0, 2),
            'avg_processing_time' => round($stats->avg_processing_time ?? 0, 2),
            'successful_requests' => $stats->successful_requests ?? 0,
            'success_rate' => $stats->total_requests > 0 
                ? round(($stats->successful_requests / $stats->total_requests) * 100, 2) 
                : 0
        ];
    }

    /**
     * Rotate API keys for security
     */
    public function rotateApiKeys(): array
    {
        $results = [];
        $providers = AIProvider::active()->get();

        foreach ($providers as $provider) {
            try {
                // For now, this is a placeholder for the rotation logic
                // In practice, you would integrate with each provider's API key management
                $results[$provider->id] = [
                    'provider_name' => $provider->name,
                    'rotated' => false,
                    'message' => 'Manual rotation required'
                ];
            } catch (\Exception $e) {
                $results[$provider->id] = [
                    'provider_name' => $provider->name,
                    'rotated' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }

    /**
     * Get load balancing recommendations
     */
    public function getLoadBalancingRecommendations(): array
    {
        $providers = AIProvider::active()->get();
        $recommendations = [];

        foreach ($providers as $provider) {
            $stats = $this->getUsageStats($provider, 7); // Last 7 days
            
            $load = $stats['total_requests'] / 7; // Average requests per day
            $efficiency = $stats['success_rate'] / 100 * (1000 / max($stats['avg_processing_time'], 1));
            
            $recommendations[] = [
                'provider_id' => $provider->id,
                'provider_name' => $provider->name,
                'current_load' => round($load, 2),
                'efficiency_score' => round($efficiency, 2),
                'recommendation' => $this->getProviderRecommendation($load, $efficiency, $stats['success_rate'])
            ];
        }

        // Sort by efficiency score descending
        usort($recommendations, function($a, $b) {
            return $b['efficiency_score'] <=> $a['efficiency_score'];
        });

        return $recommendations;
    }

    /**
     * Check if provider is available for task type
     */
    protected function isProviderAvailable(AIProvider $provider, string $taskType): bool
    {
        if (!$provider->isAvailable()) {
            return false;
        }

        // Check if provider has models for this task type
        $hasMatchingModel = $provider->activeModels()
            ->where('type', $taskType)
            ->exists();

        return $hasMatchingModel;
    }

    /**
     * Make a test request to the provider
     */
    protected function makeTestRequest(AIProvider $provider): array
    {
        switch ($provider->type) {
            case 'openai':
                return $this->testOpenAI($provider);
            case 'claude':
                return $this->testClaude($provider);
            case 'gemini':
                return $this->testGemini($provider);
            default:
                throw new \Exception("Unsupported provider type: {$provider->type}");
        }
    }

    /**
     * Test OpenAI connection
     */
    protected function testOpenAI(AIProvider $provider): array
    {
        $response = $this->httpClient->get('https://api.openai.com/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $provider->decrypted_api_key,
                'Content-Type' => 'application/json'
            ]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        
        return [
            'provider' => 'OpenAI',
            'models_available' => count($data['data'] ?? []),
            'api_version' => 'v1'
        ];
    }

    /**
     * Test Claude connection
     */
    protected function testClaude(AIProvider $provider): array
    {
        // Claude doesn't have a simple list models endpoint, so we'll test with a minimal request
        $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key' => $provider->decrypted_api_key,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01'
            ],
            'json' => [
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 10,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test']
                ]
            ]
        ]);

        return [
            'provider' => 'Claude',
            'api_version' => '2023-06-01',
            'test_request' => 'successful'
        ];
    }

    /**
     * Test Gemini connection
     */
    protected function testGemini(AIProvider $provider): array
    {
        $response = $this->httpClient->get("https://generativelanguage.googleapis.com/v1/models", [
            'query' => ['key' => $provider->decrypted_api_key]
        ]);

        $data = json_decode($response->getBody()->getContents(), true);
        
        return [
            'provider' => 'Gemini',
            'models_available' => count($data['models'] ?? []),
            'api_version' => 'v1'
        ];
    }

    /**
     * Get recommendation for provider based on performance metrics
     */
    protected function getProviderRecommendation(float $load, float $efficiency, float $successRate): string
    {
        if ($successRate < 95) {
            return 'Consider reducing load - low success rate';
        }
        
        if ($load > 1000) {
            return 'High load - consider additional capacity';
        }
        
        if ($efficiency > 50) {
            return 'Optimal performance - consider increasing load';
        }
        
        return 'Normal operation';
    }

    /**
     * Create or update a provider
     */
    public function createProvider(array $data): AIProvider
    {
        $provider = AIProvider::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'endpoint_url' => $data['endpoint_url'] ?? null,
            'max_requests_per_minute' => $data['max_requests_per_minute'] ?? 60,
            'max_tokens_per_request' => $data['max_tokens_per_request'] ?? 4000,
            'cost_per_token' => $data['cost_per_token'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'priority' => $data['priority'] ?? 1,
            'configuration' => $data['configuration'] ?? null
        ]);

        // Set API key separately to trigger encryption
        if (!empty($data['api_key'])) {
            $provider->api_key = $data['api_key'];
            $provider->save();
        }

        return $provider;
    }

    /**
     * Update provider configuration
     */
    public function updateProvider(AIProvider $provider, array $data): AIProvider
    {
        $provider->fill($data);
        
        // Handle API key update separately
        if (isset($data['api_key']) && !empty($data['api_key'])) {
            $provider->api_key = $data['api_key'];
        }
        
        $provider->save();
        
        return $provider;
    }

    /**
     * Get all providers with their current status
     */
    public function getAllProvidersStatus(): Collection
    {
        return AIProvider::with(['models' => function($query) {
            $query->active();
        }])->get()->map(function($provider) {
            $status = $this->testConnection($provider);
            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'type' => $provider->type,
                'is_active' => $provider->is_active,
                'priority' => $provider->priority,
                'models_count' => $provider->models->count(),
                'status' => $status['success'] ? 'connected' : 'error',
                'response_time' => $status['response_time'],
                'last_checked' => now()
            ];
        });
    }
}