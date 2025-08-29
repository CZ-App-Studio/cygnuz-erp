<?php

namespace Modules\AICore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AIModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'provider_id',
        'name',
        'model_identifier',
        'type',
        'max_tokens',
        'supports_streaming',
        'cost_per_input_token',
        'cost_per_output_token',
        'is_active',
        'configuration'
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'supports_streaming' => 'boolean',
        'max_tokens' => 'integer',
        'cost_per_input_token' => 'decimal:8',
        'cost_per_output_token' => 'decimal:8'
    ];

    /**
     * Get the provider that owns this model
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'provider_id');
    }

    /**
     * Get usage logs for this model
     */
    public function usageLogs(): HasMany
    {
        return $this->hasMany(AIUsageLog::class, 'model_id');
    }

    /**
     * Calculate cost for given tokens
     */
    public function calculateCost(int $inputTokens = 0, int $outputTokens = 0): float
    {
        $inputCost = $inputTokens * ($this->cost_per_input_token ?? 0);
        $outputCost = $outputTokens * ($this->cost_per_output_token ?? 0);
        
        return round($inputCost + $outputCost, 6);
    }

    /**
     * Check if model is available for use
     */
    public function isAvailable(): bool
    {
        return $this->is_active && $this->provider && $this->provider->isAvailable();
    }

    /**
     * Get model capabilities
     */
    public function getCapabilities(): array
    {
        $capabilities = [
            'max_tokens' => $this->max_tokens,
            'supports_streaming' => $this->supports_streaming,
            'type' => $this->type
        ];

        // Add configuration-based capabilities
        if ($this->configuration) {
            $capabilities = array_merge($capabilities, $this->configuration);
        }

        return $capabilities;
    }

    /**
     * Get model configuration value
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Set model configuration value
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Get usage statistics for this model
     */
    public function getUsageStats(int $days = 30): array
    {
        $logs = $this->usageLogs()
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
            'total_requests' => $logs->total_requests ?? 0,
            'total_tokens' => $logs->total_tokens ?? 0,
            'total_cost' => $logs->total_cost ?? 0,
            'avg_processing_time' => $logs->avg_processing_time ?? 0,
            'successful_requests' => $logs->successful_requests ?? 0,
            'success_rate' => $logs->total_requests > 0 
                ? round(($logs->successful_requests / $logs->total_requests) * 100, 2) 
                : 0
        ];
    }

    /**
     * Scope for active models
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific model type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for models that support streaming
     */
    public function scopeSupportsStreaming($query)
    {
        return $query->where('supports_streaming', true);
    }

    /**
     * Scope for models within token limit
     */
    public function scopeWithinTokenLimit($query, int $requiredTokens)
    {
        return $query->where('max_tokens', '>=', $requiredTokens);
    }
}
