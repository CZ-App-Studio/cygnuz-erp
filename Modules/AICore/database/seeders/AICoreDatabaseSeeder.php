<?php

namespace Modules\AICore\Database\Seeders;

use Illuminate\Database\Seeder;

class AICoreDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AICorePermissionSeeder::class,
            DefaultAIProvidersSeeder::class,
            AICoreSettingsSeeder::class,
            // SampleUsageDataSeeder::class,
        ]);
    }
}
