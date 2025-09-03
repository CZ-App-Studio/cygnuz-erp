<?php

namespace Modules\AICore\Services;

use Illuminate\Support\Collection;
use Modules\AICore\Models\AIProvider;
use Nwidart\Modules\Facades\Module;

class AIProviderAddonService
{
    /**
     * Provider addon mapping
     */
    protected array $providerAddons = [
        'openai' => 'AICore', // Free with AICore
        'claude' => 'ClaudeAIProvider',
        'gemini' => 'GeminiAIProvider',
        'azure-openai' => 'AzureOpenAIProvider',
    ];

    /**
     * Get all enabled provider types based on installed/enabled addons
     */
    public function getEnabledProviderTypes(): array
    {
        $enabledTypes = [];

        foreach ($this->providerAddons as $providerType => $addonName) {
            if ($this->isAddonEnabled($addonName)) {
                $enabledTypes[] = $providerType;
            }
        }

        return $enabledTypes;
    }

    /**
     * Get providers filtered by enabled addons
     */
    public function getEnabledProviders(): Collection
    {
        $enabledTypes = $this->getEnabledProviderTypes();

        return AIProvider::whereIn('type', $enabledTypes)->get();
    }

    /**
     * Get active providers filtered by enabled addons
     */
    public function getActiveEnabledProviders(): Collection
    {
        $enabledTypes = $this->getEnabledProviderTypes();

        return AIProvider::active()
            ->whereIn('type', $enabledTypes)
            ->get();
    }

    /**
     * Check if a specific provider type is enabled
     */
    public function isProviderTypeEnabled(string $providerType): bool
    {
        if (! isset($this->providerAddons[$providerType])) {
            return false;
        }

        $addonName = $this->providerAddons[$providerType];

        return $this->isAddonEnabled($addonName);
    }

    /**
     * Check if an addon is enabled
     */
    protected function isAddonEnabled(string $addonName): bool
    {
        // AICore is always enabled (base module)
        if ($addonName === 'AICore') {
            return true;
        }

        $module = Module::find($addonName);

        return $module && $module->isEnabled();
    }

    /**
     * Get available provider addons (for marketplace/purchase info)
     */
    public function getAvailableProviderAddons(): array
    {
        $allAddons = [
            'ClaudeAIProvider' => [
                'name' => 'Claude AI Provider (Anthropic)',
                'description' => 'Integrate advanced Claude models for superior text generation and analysis.',
                'provider_types' => ['claude'],
                'models_count' => 4,
                'price' => '$49.00',
                'features' => [
                    'Claude 3.5 Sonnet', 'Claude 3 Opus', 'Claude 3 Sonnet', 'Claude 3 Haiku',
                    'High context windows', 'Advanced reasoning',
                ],
            ],
            'GeminiAIProvider' => [
                'name' => 'Google Gemini Provider',
                'description' => 'Access Google\'s powerful Gemini models for multimodal and text generation tasks.',
                'provider_types' => ['gemini'],
                'models_count' => 4,
                'price' => '$39.00',
                'features' => [
                    'Gemini 1.5 Pro', 'Gemini 1.5 Flash', 'Gemini Pro', 'Gemini Pro Vision',
                    'Multimodal capabilities', 'Large context windows',
                ],
            ],
            'AzureOpenAIProvider' => [
                'name' => 'Azure OpenAI Provider',
                'description' => 'Leverage OpenAI models hosted on Microsoft Azure for enterprise-grade AI.',
                'provider_types' => ['azure-openai'],
                'models_count' => 2,
                'price' => '$29.00',
                'features' => [
                    'Azure GPT-4', 'Azure GPT-3.5 Turbo',
                    'Enterprise security', 'Azure integration',
                ],
            ],
        ];

        // Filter to only show addons that are not currently enabled
        $availableAddons = [];
        foreach ($allAddons as $addonName => $addonInfo) {
            if (! $this->isAddonEnabled($addonName)) {
                $availableAddons[$addonName] = $addonInfo;
            }
        }

        return $availableAddons;
    }

    /**
     * Get installed but disabled provider addons
     */
    public function getDisabledProviderAddons(): array
    {
        $disabled = [];

        foreach ($this->providerAddons as $providerType => $addonName) {
            if ($addonName === 'AICore') {
                continue;
            } // Skip core module

            $module = Module::find($addonName);
            if ($module && ! $module->isEnabled()) {
                $disabled[] = [
                    'addon_name' => $addonName,
                    'provider_type' => $providerType,
                    'status' => 'installed_disabled',
                ];
            }
        }

        return $disabled;
    }

    /**
     * Get provider addon info by type
     */
    public function getProviderAddonInfo(string $providerType): ?array
    {
        if (! isset($this->providerAddons[$providerType])) {
            return null;
        }

        $addonName = $this->providerAddons[$providerType];
        $available = $this->getAvailableProviderAddons();

        return [
            'addon_name' => $addonName,
            'provider_type' => $providerType,
            'is_enabled' => $this->isAddonEnabled($addonName),
            'is_core' => $addonName === 'AICore',
            'info' => $available[$addonName] ?? null,
        ];
    }
}
