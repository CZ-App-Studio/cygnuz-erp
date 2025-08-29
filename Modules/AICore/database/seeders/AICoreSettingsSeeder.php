<?php

namespace Modules\AICore\database\seeders;

use App\Services\Settings\ModuleSettingsService;
use Illuminate\Database\Seeder;

class AICoreSettingsSeeder extends Seeder
{
    protected ModuleSettingsService $settingsService;

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings
            'aicore.ai_enabled' => true,
            'aicore.default_temperature' => 0.7,
            'aicore.default_max_tokens' => 1000,
            'aicore.request_timeout' => 30,
            'aicore.daily_token_limit' => 100000,

            // Cost Controls
            'aicore.monthly_budget' => 100,

            // Rate Limiting
            'aicore.rate_limit_enabled' => true,
            'aicore.global_rate_limit' => 60,
            'aicore.user_rate_limit' => 20,

            // Security
            'aicore.log_requests' => true,
            'aicore.data_retention_days' => 90,

            // Cache
            'aicore.cache_enabled' => true,
            'aicore.cache_ttl' => 3600,
        ];

        foreach ($settings as $key => $value) {
            // Extract module name from key (aicore.setting_name)
            [$module, $settingKey] = explode('.', $key, 2);
            $this->settingsService->set('AICore', $key, $value);
        }
    }
}
