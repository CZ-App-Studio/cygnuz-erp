<?php

namespace Modules\AICore\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Models\AIModel;

class DefaultAIProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default AI providers (OpenAI comes free with AI Core)
        $this->createOpenAIProvider();
        
        // Note: Other providers are available as separate paid addons:
        // - Claude AI Provider (Anthropic) - ClaudeAIProvider addon
        // - Google Gemini - GeminiAIProvider addon  
        // - Azure OpenAI - AzureOpenAIProvider addon
        // - Other providers available in marketplace
    }

    /**
     * Create OpenAI provider and models
     */
    private function createOpenAIProvider()
    {
        $provider = AIProvider::create([
            'name' => 'OpenAI',
            'type' => 'openai',
            'endpoint_url' => 'https://api.openai.com/v1',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 60,
            'max_tokens_per_request' => 8192,
            'cost_per_token' => 0.000015, // Average cost
            'is_active' => true,
            'priority' => 1,
            'configuration' => [
                'api_version' => 'v1',
                'organization' => null,
                'timeout' => 30
            ]
        ]);

        // Create OpenAI models
        $this->createOpenAIModels($provider);
    }

    /**
     * Create OpenAI models
     */
    private function createOpenAIModels(AIProvider $provider)
    {
        $models = [
            // GPT-5 Series (Released August 2025)
            [
                'name' => 'GPT-5',
                'model_identifier' => 'gpt-5',
                'type' => 'text',
                'max_tokens' => 128000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000015,
                'cost_per_output_token' => 0.00006,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 272000,
                    'supports_vision' => true,
                    'reasoning_levels' => ['minimal', 'low', 'medium', 'high'],
                    'description' => 'Smartest, fastest, most useful model with built-in thinking'
                ]
            ],
            [
                'name' => 'GPT-5 Mini',
                'model_identifier' => 'gpt-5-mini',
                'type' => 'text',
                'max_tokens' => 128000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000003,
                'cost_per_output_token' => 0.000015,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 272000,
                    'supports_vision' => true,
                    'reasoning_levels' => ['minimal', 'low', 'medium', 'high'],
                    'description' => 'Efficient GPT-5 variant for everyday tasks'
                ]
            ],
            [
                'name' => 'GPT-5 Nano',
                'model_identifier' => 'gpt-5-nano',
                'type' => 'text',
                'max_tokens' => 128000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000015,
                'cost_per_output_token' => 0.000006,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 272000,
                    'reasoning_levels' => ['minimal', 'low', 'medium', 'high'],
                    'description' => 'Smallest GPT-5 variant for simple tasks'
                ]
            ],
            // o3 and o4 Series (Advanced Reasoning Models)
            [
                'name' => 'o3',
                'model_identifier' => 'o3',
                'type' => 'text',
                'max_tokens' => 100000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000015,
                'cost_per_output_token' => 0.00006,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 200000,
                    'reasoning_model' => true,
                    'supports_vision' => true,
                    'description' => 'Powerful reasoning model for complex multi-faceted analysis'
                ]
            ],
            [
                'name' => 'o3-mini',
                'model_identifier' => 'o3-mini-2025-01-31',
                'type' => 'text',
                'max_tokens' => 100000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000011,
                'cost_per_output_token' => 0.0000044,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 200000,
                    'reasoning_model' => true,
                    'description' => 'Fast, cost-efficient reasoning model for coding, math, and science'
                ]
            ],
            [
                'name' => 'o4-mini',
                'model_identifier' => 'o4-mini',
                'type' => 'text',
                'max_tokens' => 100000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000011,
                'cost_per_output_token' => 0.0000044,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 200000,
                    'reasoning_model' => true,
                    'supports_vision' => true,
                    'description' => 'Compact, efficient reasoning model excelling in math, coding, and visual tasks'
                ]
            ],
            // GPT-4.1 Series (1 Million Token Context)
            [
                'name' => 'GPT-4.1',
                'model_identifier' => 'gpt-4.1',
                'type' => 'text',
                'max_tokens' => 16384,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000005,
                'cost_per_output_token' => 0.000015,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 1000000,
                    'supports_vision' => true,
                    'description' => 'Advanced general-purpose model with 1M token context and June 2024 knowledge'
                ]
            ],
            [
                'name' => 'GPT-4.1 Mini',
                'model_identifier' => 'gpt-4.1-mini',
                'type' => 'text',
                'max_tokens' => 16384,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000015,
                'cost_per_output_token' => 0.000006,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 1000000,
                    'supports_vision' => true,
                    'description' => 'Efficient GPT-4.1 variant with 1M token context'
                ]
            ],
            [
                'name' => 'GPT-4.1 Nano',
                'model_identifier' => 'gpt-4.1-nano',
                'type' => 'text',
                'max_tokens' => 16384,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000075,
                'cost_per_output_token' => 0.000003,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 1000000,
                    'description' => 'Smallest GPT-4.1 variant for simple tasks'
                ]
            ],
            // GPT-4o Series (Still Available)
            [
                'name' => 'GPT-4o',
                'model_identifier' => 'gpt-4o',
                'type' => 'multimodal',
                'max_tokens' => 16384,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000025,
                'cost_per_output_token' => 0.00001,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 128000,
                    'supports_images' => true,
                    'supports_audio' => true,
                    'description' => 'Multimodal model with vision and audio capabilities'
                ]
            ],
            [
                'name' => 'GPT-4o Mini',
                'model_identifier' => 'gpt-4o-mini',
                'type' => 'text',
                'max_tokens' => 16384,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000015,
                'cost_per_output_token' => 0.0000006,
                'is_active' => true,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 128000,
                    'supports_vision' => true,
                    'description' => 'Most cost-efficient small model with vision capabilities'
                ]
            ],
            // Legacy o1 Series (Being phased out)
            [
                'name' => 'o1',
                'model_identifier' => 'o1',
                'type' => 'text',
                'max_tokens' => 32768,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000015,
                'cost_per_output_token' => 0.00006,
                'is_active' => false,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 128000,
                    'reasoning_model' => true,
                    'description' => 'Legacy reasoning model (superseded by o3)'
                ]
            ],
            [
                'name' => 'o1-mini',
                'model_identifier' => 'o1-mini',
                'type' => 'text',
                'max_tokens' => 65536,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000011,
                'cost_per_output_token' => 0.0000044,
                'is_active' => false,
                'configuration' => [
                    'temperature_range' => [0, 2],
                    'default_temperature' => 0.7,
                    'supports_functions' => true,
                    'context_window' => 128000,
                    'reasoning_model' => true,
                    'description' => 'Legacy mini reasoning model (superseded by o3-mini)'
                ]
            ],
            // Embedding Models
            [
                'name' => 'Text Embedding 3 Large',
                'model_identifier' => 'text-embedding-3-large',
                'type' => 'embedding',
                'max_tokens' => 8191,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.00000013,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'dimensions' => 3072,
                    'use_case' => 'semantic_search',
                    'description' => 'Most capable embedding model for semantic search'
                ]
            ],
            [
                'name' => 'Text Embedding 3 Small',
                'model_identifier' => 'text-embedding-3-small',
                'type' => 'embedding',
                'max_tokens' => 8191,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.00000002,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'dimensions' => 1536,
                    'use_case' => 'semantic_search',
                    'description' => 'Efficient embedding model for semantic search'
                ]
            ],
            // Image Generation Models
            [
                'name' => 'DALL-E 3',
                'model_identifier' => 'dall-e-3',
                'type' => 'image',
                'max_tokens' => 0,
                'supports_streaming' => false,
                'cost_per_input_token' => 0,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'sizes' => ['1024x1024', '1024x1792', '1792x1024'],
                    'quality' => ['standard', 'hd'],
                    'style' => ['vivid', 'natural'],
                    'price_standard_1024' => 0.040,
                    'price_standard_wide' => 0.080,
                    'price_hd_1024' => 0.080,
                    'price_hd_wide' => 0.120,
                    'description' => 'Advanced image generation with natural language prompts'
                ]
            ],
            [
                'name' => 'DALL-E 2',
                'model_identifier' => 'dall-e-2',
                'type' => 'image',
                'max_tokens' => 0,
                'supports_streaming' => false,
                'cost_per_input_token' => 0,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'sizes' => ['256x256', '512x512', '1024x1024'],
                    'price_256' => 0.016,
                    'price_512' => 0.018,
                    'price_1024' => 0.020,
                    'description' => 'Image generation with multiple size options'
                ]
            ],
            // Audio Models
            [
                'name' => 'Whisper',
                'model_identifier' => 'whisper-1',
                'type' => 'audio',
                'max_tokens' => 0,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.00000006, // $0.006 per minute
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'languages' => 'multiple',
                    'output_formats' => ['json', 'text', 'srt', 'vtt'],
                    'description' => 'Audio transcription in multiple languages'
                ]
            ],
            [
                'name' => 'TTS-1',
                'model_identifier' => 'tts-1',
                'type' => 'audio',
                'max_tokens' => 0,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000015, // $15 per 1M chars
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'voices' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'],
                    'formats' => ['mp3', 'opus', 'aac', 'flac'],
                    'description' => 'Text-to-speech with multiple voice options'
                ]
            ],
            [
                'name' => 'TTS-1 HD',
                'model_identifier' => 'tts-1-hd',
                'type' => 'audio',
                'max_tokens' => 0,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00003, // $30 per 1M chars
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'voices' => ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'],
                    'formats' => ['mp3', 'opus', 'aac', 'flac'],
                    'description' => 'High-quality text-to-speech with multiple voice options'
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Claude provider and models
     */
    private function createClaudeProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Claude (Anthropic)',
            'type' => 'claude',
            'endpoint_url' => 'https://api.anthropic.com/v1',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 50,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000009, // Average cost
            'is_active' => true,
            'priority' => 2,
            'configuration' => [
                'api_version' => '2023-06-01',
                'timeout' => 30
            ]
        ]);

        // Create Claude models (latest versions)
        $models = [
            [
                'name' => 'Claude 4 Sonnet',
                'model_identifier' => 'claude-4-sonnet',
                'type' => 'text',
                'max_tokens' => 64000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000003,
                'cost_per_output_token' => 0.000015,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 200000,
                    'max_output_tokens' => 64000,
                    'reasoning_model' => true,
                    'supports_vision' => true,
                    'description' => 'High performance model with great reasoning capabilities'
                ]
            ],
            [
                'name' => 'Claude 3.7 Sonnet',
                'model_identifier' => 'claude-3-7-sonnet-20250219',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000015,
                'cost_per_output_token' => 0.000075,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 200000,
                    'max_output_tokens' => 8192,
                    'hybrid_reasoning' => true,
                    'supports_vision' => true,
                    'description' => 'First hybrid reasoning model with extended thinking mode'
                ]
            ],
            [
                'name' => 'Claude 3.5 Sonnet (New)',
                'model_identifier' => 'claude-3-5-sonnet-20241022',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000003,
                'cost_per_output_token' => 0.000015,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 200000,
                    'max_output_tokens' => 8192,
                    'supports_vision' => true,
                    'description' => 'Superior reasoning, coding proficiency, and computer vision'
                ]
            ],
            [
                'name' => 'Claude 3.5 Haiku',
                'model_identifier' => 'claude-3-5-haiku-20241022',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000025,
                'cost_per_output_token' => 0.00000125,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 200000,
                    'max_output_tokens' => 8192,
                    'supports_vision' => true,
                    'description' => 'Fastest model with improved instruction following and vision'
                ]
            ],
            [
                'name' => 'Claude 3 Opus',
                'model_identifier' => 'claude-3-opus-20240229',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000015,
                'cost_per_output_token' => 0.000075,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 200000,
                    'max_output_tokens' => 4096,
                    'supports_vision' => true,
                    'description' => 'Most powerful Claude 3 model for complex tasks'
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Gemini provider and models
     */
    private function createGeminiProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Google Gemini',
            'type' => 'gemini',
            'endpoint_url' => 'https://generativelanguage.googleapis.com/v1',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 60,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000001, // Very low cost
            'is_active' => true,
            'priority' => 3,
            'configuration' => [
                'api_version' => 'v1',
                'timeout' => 30
            ]
        ]);

        // Create Gemini models
        $models = [
            [
                'name' => 'Gemini 2.5 Pro',
                'model_identifier' => 'gemini-2.5-pro',
                'type' => 'multimodal',
                'max_tokens' => 64000,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000125,
                'cost_per_output_token' => 0.00001,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 2000000,
                    'supports_multimodal' => true,
                    'supports_thinking' => true,
                    'supports_audio' => true,
                    'supports_video' => true,
                    'description' => 'Most intelligent model designed as thinking model with superior reasoning'
                ]
            ],
            [
                'name' => 'Gemini 2.5 Flash',
                'model_identifier' => 'gemini-2.5-flash',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000037,
                'cost_per_output_token' => 0.00000075,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 1000000,
                    'supports_multimodal' => true,
                    'description' => 'Experimental model optimized for speed and efficiency'
                ]
            ],
            [
                'name' => 'Gemini 2.0 Flash Thinking',
                'model_identifier' => 'gemini-2.0-flash-thinking-exp',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000037,
                'cost_per_output_token' => 0.00000075,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 32768,
                    'supports_multimodal' => true,
                    'thinking_mode' => true,
                    'description' => 'Experimental thinking model with visible reasoning'
                ]
            ],
            [
                'name' => 'Gemini 2.0 Pro',
                'model_identifier' => 'gemini-2.0-pro',
                'type' => 'multimodal',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000125,
                'cost_per_output_token' => 0.00000375,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 2000000,
                    'supports_multimodal' => true,
                    'supports_audio' => true,
                    'supports_video' => true,
                    'description' => 'Advanced multimodal model with superior capabilities'
                ]
            ],
            [
                'name' => 'Gemini 2.0 Flash',
                'model_identifier' => 'gemini-2.0-flash',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000037,
                'cost_per_output_token' => 0.00000075,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 1000000,
                    'supports_multimodal' => true,
                    'supports_realtime' => true,
                    'description' => 'Faster model for everyday tasks with native multimodal functionality'
                ]
            ],
            [
                'name' => 'Gemini 1.5 Pro (002)',
                'model_identifier' => 'gemini-1.5-pro-002',
                'type' => 'multimodal',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000125,
                'cost_per_output_token' => 0.00000375,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 2000000,
                    'supports_multimodal' => true,
                    'description' => 'Enhanced version of Gemini 1.5 Pro'
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Azure OpenAI provider and models
     */
    private function createAzureOpenAIProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Azure OpenAI',
            'type' => 'azure-openai',
            'endpoint_url' => 'https://YOUR_RESOURCE_NAME.openai.azure.com',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 120,
            'max_tokens_per_request' => 8192,
            'cost_per_token' => 0.000015,
            'is_active' => true,
            'priority' => 4,
            'configuration' => [
                'api_version' => '2024-02-01',
                'deployment_name' => 'gpt-4',
                'resource_name' => 'YOUR_RESOURCE_NAME',
                'timeout' => 30
            ]
        ]);

        $models = [
            [
                'name' => 'Azure GPT-4',
                'model_identifier' => 'gpt-4',
                'type' => 'text',
                'max_tokens' => 8192,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00003,
                'cost_per_output_token' => 0.00006,
                'is_active' => true,
                'configuration' => [
                    'deployment_name' => 'gpt-4',
                    'context_window' => 8192
                ]
            ],
            [
                'name' => 'Azure GPT-3.5 Turbo',
                'model_identifier' => 'gpt-35-turbo',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000015,
                'cost_per_output_token' => 0.000002,
                'is_active' => true,
                'configuration' => [
                    'deployment_name' => 'gpt-35-turbo',
                    'context_window' => 16385
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Cohere provider and models
     */
    private function createCohereProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Cohere',
            'type' => 'cohere',
            'endpoint_url' => 'https://api.cohere.ai/v1',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 100,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000015,
            'is_active' => true,
            'priority' => 5,
            'configuration' => [
                'api_version' => 'v1',
                'timeout' => 30
            ]
        ]);

        $models = [
            [
                'name' => 'Command R+',
                'model_identifier' => 'command-r-plus',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000003,
                'cost_per_output_token' => 0.000015,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 128000,
                    'supports_rag' => true
                ]
            ],
            [
                'name' => 'Command R',
                'model_identifier' => 'command-r',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.0000005,
                'cost_per_output_token' => 0.0000015,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 128000,
                    'supports_rag' => true
                ]
            ],
            [
                'name' => 'Embed English',
                'model_identifier' => 'embed-english-v3.0',
                'type' => 'embedding',
                'max_tokens' => 512,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000001,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'dimensions' => 1024,
                    'input_type' => 'search_document'
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Hugging Face provider and models
     */
    private function createHuggingFaceProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Hugging Face',
            'type' => 'huggingface',
            'endpoint_url' => 'https://api-inference.huggingface.co/models',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 1000,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000001,
            'is_active' => true,
            'priority' => 6,
            'configuration' => [
                'api_version' => 'v1',
                'timeout' => 30
            ]
        ]);

        $models = [
            [
                'name' => 'Llama 2 70B Chat',
                'model_identifier' => 'meta-llama/Llama-2-70b-chat-hf',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000005,
                'cost_per_output_token' => 0.0000005,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 4096
                ]
            ],
            [
                'name' => 'CodeLlama 34B Instruct',
                'model_identifier' => 'codellama/CodeLlama-34b-Instruct-hf',
                'type' => 'code',
                'max_tokens' => 4096,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000005,
                'cost_per_output_token' => 0.0000005,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 16384,
                    'specialization' => 'code_generation'
                ]
            ],
            [
                'name' => 'BERT Base Embeddings',
                'model_identifier' => 'sentence-transformers/all-MiniLM-L6-v2',
                'type' => 'embedding',
                'max_tokens' => 512,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.00000001,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'dimensions' => 384
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Mistral AI provider and models
     */
    private function createMistralProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Mistral AI',
            'type' => 'mistral',
            'endpoint_url' => 'https://api.mistral.ai/v1',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 60,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000007,
            'is_active' => true,
            'priority' => 7,
            'configuration' => [
                'api_version' => 'v1',
                'timeout' => 30
            ]
        ]);

        $models = [
            [
                'name' => 'Mistral Large',
                'model_identifier' => 'mistral-large-latest',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000008,
                'cost_per_output_token' => 0.000024,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 32768
                ]
            ],
            [
                'name' => 'Mistral Medium',
                'model_identifier' => 'mistral-medium-latest',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000275,
                'cost_per_output_token' => 0.0000081,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 32768
                ]
            ],
            [
                'name' => 'Mistral Small',
                'model_identifier' => 'mistral-small-latest',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000002,
                'cost_per_output_token' => 0.000006,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 32768
                ]
            ],
            [
                'name' => 'Mistral Embed',
                'model_identifier' => 'mistral-embed',
                'type' => 'embedding',
                'max_tokens' => 8192,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000001,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'dimensions' => 1024
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create Ollama provider and models
     */
    private function createOllamaProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Ollama (Local)',
            'type' => 'ollama',
            'endpoint_url' => 'http://localhost:11434/v1',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 1000,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0, // Local is free
            'is_active' => false, // Disabled by default
            'priority' => 8,
            'configuration' => [
                'api_version' => 'v1',
                'timeout' => 60,
                'local' => true
            ]
        ]);

        $models = [
            [
                'name' => 'Llama 3.1 8B',
                'model_identifier' => 'llama3.1:8b',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 128000,
                    'local_model' => true
                ]
            ],
            [
                'name' => 'Code Llama 13B',
                'model_identifier' => 'codellama:13b',
                'type' => 'code',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 16384,
                    'local_model' => true,
                    'specialization' => 'code_generation'
                ]
            ],
            [
                'name' => 'Mistral 7B',
                'model_identifier' => 'mistral:7b',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 32768,
                    'local_model' => true
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create AWS Bedrock provider and models
     */
    private function createBedrockProvider()
    {
        $provider = AIProvider::create([
            'name' => 'AWS Bedrock',
            'type' => 'bedrock',
            'endpoint_url' => 'https://bedrock-runtime.us-east-1.amazonaws.com',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 100,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000008,
            'is_active' => true,
            'priority' => 9,
            'configuration' => [
                'region' => 'us-east-1',
                'aws_access_key_id' => 'XXX',
                'aws_secret_access_key' => 'XXX',
                'timeout' => 30
            ]
        ]);

        $models = [
            [
                'name' => 'Claude 3 Sonnet (Bedrock)',
                'model_identifier' => 'anthropic.claude-3-sonnet-20240229-v1:0',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.000003,
                'cost_per_output_token' => 0.000015,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 200000
                ]
            ],
            [
                'name' => 'Llama 2 70B (Bedrock)',
                'model_identifier' => 'meta.llama2-70b-chat-v1',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => true,
                'cost_per_input_token' => 0.00000195,
                'cost_per_output_token' => 0.00000256,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 4096
                ]
            ],
            [
                'name' => 'Titan Embeddings',
                'model_identifier' => 'amazon.titan-embed-text-v1',
                'type' => 'embedding',
                'max_tokens' => 8000,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000001,
                'cost_per_output_token' => 0,
                'is_active' => true,
                'configuration' => [
                    'dimensions' => 1536
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }

    /**
     * Create PaLM provider and models
     */
    private function createPalmProvider()
    {
        $provider = AIProvider::create([
            'name' => 'Google PaLM',
            'type' => 'palm',
            'endpoint_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'api_key_encrypted' => Crypt::encryptString('XXX'),
            'max_requests_per_minute' => 60,
            'max_tokens_per_request' => 4096,
            'cost_per_token' => 0.000001,
            'is_active' => true,
            'priority' => 10,
            'configuration' => [
                'api_version' => 'v1beta',
                'timeout' => 30
            ]
        ]);

        $models = [
            [
                'name' => 'PaLM 2 Chat',
                'model_identifier' => 'chat-bison-001',
                'type' => 'text',
                'max_tokens' => 4096,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000005,
                'cost_per_output_token' => 0.0000005,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 8192
                ]
            ],
            [
                'name' => 'PaLM 2 Text',
                'model_identifier' => 'text-bison-001',
                'type' => 'text',
                'max_tokens' => 1024,
                'supports_streaming' => false,
                'cost_per_input_token' => 0.0000005,
                'cost_per_output_token' => 0.0000005,
                'is_active' => true,
                'configuration' => [
                    'context_window' => 8192
                ]
            ]
        ];

        foreach ($models as $modelData) {
            AIModel::create(array_merge($modelData, [
                'provider_id' => $provider->id
            ]));
        }
    }
}