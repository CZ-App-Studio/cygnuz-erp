<?php

namespace Modules\AICore\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIModuleConfiguration extends Model
{
    use HasFactory;

    protected $table = 'ai_module_configurations';

    protected $fillable = [
        'module_name',
        'module_display_name',
        'module_description',
        'default_provider_id',
        'default_model_id',
        'allowed_providers',
        'allowed_models',
        'settings',
        'max_tokens_limit',
        'temperature_default',
        'streaming_enabled',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'allowed_providers' => 'array',
        'allowed_models' => 'array',
        'settings' => 'array',
        'temperature_default' => 'float',
        'streaming_enabled' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_tokens_limit' => 'integer',
    ];

    /**
     * Get the default provider for this module
     */
    public function defaultProvider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'default_provider_id');
    }

    /**
     * Get the default model for this module
     */
    public function defaultModel(): BelongsTo
    {
        return $this->belongsTo(AIModel::class, 'default_model_id');
    }

    /**
     * Get allowed providers for this module
     */
    public function getAllowedProviders()
    {
        if (empty($this->allowed_providers)) {
            return AIProvider::active()->get();
        }

        return AIProvider::whereIn('id', $this->allowed_providers)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Get allowed models for this module
     */
    public function getAllowedModels()
    {
        if (empty($this->allowed_models)) {
            return AIModel::active()->get();
        }

        return AIModel::whereIn('id', $this->allowed_models)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Check if a provider is allowed for this module
     */
    public function isProviderAllowed(int $providerId): bool
    {
        if (empty($this->allowed_providers)) {
            return true; // All providers allowed if not specified
        }

        return in_array($providerId, $this->allowed_providers);
    }

    /**
     * Check if a model is allowed for this module
     */
    public function isModelAllowed(int $modelId): bool
    {
        if (empty($this->allowed_models)) {
            return true; // All models allowed if not specified
        }

        return in_array($modelId, $this->allowed_models);
    }

    /**
     * Get configuration value
     */
    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function setConfigValue(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->settings = $settings;
    }

    /**
     * Scope for active modules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope ordered by priority
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'asc')->orderBy('module_display_name', 'asc');
    }
}
