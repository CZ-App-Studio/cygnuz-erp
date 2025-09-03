<?php

return [
    // Default AI provider settings
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'fallback_providers' => ['claude', 'gemini'],

    // Request settings
    'timeout' => env('AI_TIMEOUT', 30),
    'connect_timeout' => env('AI_CONNECT_TIMEOUT', 10),
    'max_retries' => env('AI_MAX_RETRIES', 3),

    // Rate limiting
    'rate_limits' => [
        'requests_per_minute' => env('AI_REQUESTS_PER_MINUTE', 60),
        'tokens_per_hour' => env('AI_TOKENS_PER_HOUR', 100000),
    ],

    // Cost management
    'costs' => [
        'alert_threshold' => env('AI_COST_ALERT_THRESHOLD', 100), // USD
        'daily_limit' => env('AI_DAILY_COST_LIMIT', 500), // USD
        'monthly_limit' => env('AI_MONTHLY_COST_LIMIT', 10000), // USD
    ],

    // Caching
    'cache' => [
        'enabled' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'driver' => env('AI_CACHE_DRIVER', 'redis'),
    ],

    // Default model configurations
    'models' => [
        'openai' => [
            'gpt-4' => [
                'max_tokens' => 8192,
                'cost_per_input_token' => 0.00003,
                'cost_per_output_token' => 0.00006,
                'supports_streaming' => true,
            ],
            'gpt-3.5-turbo' => [
                'max_tokens' => 4096,
                'cost_per_input_token' => 0.0000015,
                'cost_per_output_token' => 0.000002,
                'supports_streaming' => true,
            ],
        ],
        'claude' => [
            'claude-3-sonnet-20240229' => [
                'max_tokens' => 4096,
                'cost_per_input_token' => 0.000003,
                'cost_per_output_token' => 0.000015,
                'supports_streaming' => false,
            ],
            'claude-3-haiku-20240307' => [
                'max_tokens' => 4096,
                'cost_per_input_token' => 0.00000025,
                'cost_per_output_token' => 0.00000125,
                'supports_streaming' => false,
            ],
        ],
    ],

    // Logging and monitoring
    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'log_level' => env('AI_LOG_LEVEL', 'info'),
        'include_requests' => env('AI_LOG_REQUESTS', false),
        'include_responses' => env('AI_LOG_RESPONSES', false),
    ],

    // Security settings
    'security' => [
        'encrypt_api_keys' => true,
        'key_rotation_days' => 90,
        'max_request_size' => 1024 * 1024, // 1MB
        'allowed_file_types' => ['txt', 'pdf', 'doc', 'docx'],
    ],

    // Default quotas for new companies
    'default_quotas' => [
        'daily_token_limit' => 100000,
        'monthly_cost_limit' => 1000,
        'daily_request_limit' => 1000,
        'concurrent_requests' => 10,
    ],

    // Feature flags
    'features' => [
        'auto_fallback' => env('AI_AUTO_FALLBACK', true),
        'cost_optimization' => env('AI_COST_OPTIMIZATION', true),
        'performance_monitoring' => env('AI_PERFORMANCE_MONITORING', true),
        'usage_analytics' => env('AI_USAGE_ANALYTICS', true),
    ],
];
