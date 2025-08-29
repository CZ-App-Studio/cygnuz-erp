<?php

namespace Modules\AICore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Crypt;

class AIProvider extends Model
{
    use HasFactory;

    protected $table = 'ai_providers';

    protected $fillable = [
        'name',
        'type',
        'endpoint_url',
        'api_key_encrypted',
        'max_requests_per_minute',
        'max_tokens_per_request',
        'cost_per_token',
        'is_active',
        'priority',
        'configuration'
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_active' => 'boolean',
        'cost_per_token' => 'decimal:8',
        'max_requests_per_minute' => 'integer',
        'max_tokens_per_request' => 'integer',
        'priority' => 'integer'
    ];

    protected $hidden = [
        'api_key_encrypted'
    ];

    /**
     * Get all models for this provider
     */
    public function models(): HasMany
    {
        return $this->hasMany(AIModel::class, 'provider_id');
    }

    /**
     * Get active models for this provider
     */
    public function activeModels(): HasMany
    {
        return $this->models()->where('is_active', true);
    }

    /**
     * Get usage logs for this provider
     */
    public function usageLogs(): HasManyThrough
    {
        return $this->hasManyThrough(AIUsageLog::class, AIModel::class, 'provider_id', 'model_id');
    }

    /**
     * Get the decrypted API key
     */
    public function getDecryptedApiKeyAttribute(): ?string
    {
        if (!$this->api_key_encrypted) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Set the encrypted API key
     */
    public function setApiKeyAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['api_key_encrypted'] = Crypt::encryptString($value);
        } else {
            $this->attributes['api_key_encrypted'] = null;
        }
    }

    /**
     * Check if provider is available for requests
     */
    public function isAvailable(): bool
    {
        return $this->is_active && !empty($this->decrypted_api_key);
    }

    /**
     * Get provider configuration value
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Set provider configuration value
     */
    public function setConfigValue(string $key, $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Scope for active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific provider type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope ordered by priority
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }
}
