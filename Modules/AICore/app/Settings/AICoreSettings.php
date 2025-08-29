<?php

namespace Modules\AICore\app\Settings;

use App\Services\Settings\BaseModuleSettings;

class AICoreSettings extends BaseModuleSettings
{
    protected string $module = 'AICore';

    public function getModuleName(): string
    {
        return __('AI Core Settings');
    }

    public function getModuleDescription(): string
    {
        return __('Configure AI providers, models, cost controls, security, and performance settings for the AI Core module');
    }

    public function getModuleIcon(): string
    {
        return 'bx bx-brain';
    }

    protected function define(): array
    {
        return [
            'general' => [
                'ai_enabled' => [
                    'type' => 'toggle',
                    'label' => __('Enable AI Features'),
                    'help' => __('Enable or disable AI functionality system-wide'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'default_temperature' => [
                    'type' => 'range',
                    'label' => __('Default Temperature'),
                    'help' => __('Default creativity level for AI responses (0 = deterministic, 2 = very creative)'),
                    'default' => 0.7,
                    'min' => 0,
                    'max' => 2,
                    'step' => 0.1,
                    'validation' => 'numeric|min:0|max:2',
                ],
                'default_max_tokens' => [
                    'type' => 'number',
                    'label' => __('Default Max Tokens'),
                    'help' => __('Default maximum tokens for AI responses'),
                    'default' => 1000,
                    'validation' => 'integer|min:1|max:32000',
                ],
                'request_timeout' => [
                    'type' => 'number',
                    'label' => __('Request Timeout (seconds)'),
                    'help' => __('Maximum time to wait for AI API responses'),
                    'default' => 30,
                    'validation' => 'integer|min:5|max:300',
                ],
                'daily_token_limit' => [
                    'type' => 'number',
                    'label' => __('Daily Token Limit'),
                    'help' => __('Maximum tokens per day per company (0 = unlimited)'),
                    'default' => 100000,
                    'validation' => 'integer|min:0|max:10000000',
                ],
            ],
            'cost_controls' => [
                'monthly_budget' => [
                    'type' => 'number',
                    'label' => __('Monthly Budget (USD)'),
                    'help' => __('Maximum monthly spending on AI services (0 = unlimited)'),
                    'default' => 100,
                    'validation' => 'numeric|min:0',
                ],
            ],
            'rate_limiting' => [
                'rate_limit_enabled' => [
                    'type' => 'toggle',
                    'label' => __('Enable Rate Limiting'),
                    'help' => __('Enable rate limiting for AI requests'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'global_rate_limit' => [
                    'type' => 'number',
                    'label' => __('Global Rate Limit'),
                    'help' => __('Maximum requests per minute system-wide'),
                    'default' => 60,
                    'validation' => 'integer|min:1|max:1000',
                ],
                'user_rate_limit' => [
                    'type' => 'number',
                    'label' => __('User Rate Limit'),
                    'help' => __('Maximum requests per minute per user'),
                    'default' => 20,
                    'validation' => 'integer|min:1|max:100',
                ],
            ],
            'security' => [
                'log_requests' => [
                    'type' => 'toggle',
                    'label' => __('Log AI Requests'),
                    'help' => __('Log all AI requests for monitoring and debugging'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'data_retention_days' => [
                    'type' => 'number',
                    'label' => __('Data Retention (days)'),
                    'help' => __('How long to keep AI request logs (0 = forever)'),
                    'default' => 90,
                    'validation' => 'integer|min:0|max:365',
                ],
            ],
            'cache' => [
                'cache_enabled' => [
                    'type' => 'toggle',
                    'label' => __('Enable Response Caching'),
                    'help' => __('Cache AI responses for similar requests'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'cache_ttl' => [
                    'type' => 'number',
                    'label' => __('Cache TTL (seconds)'),
                    'help' => __('How long to cache AI responses in seconds'),
                    'default' => 3600,
                    'validation' => 'integer|min:60|max:86400',
                ],
            ],
        ];
    }
}
