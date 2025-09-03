<?php

namespace Modules\AICore\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIUsageLog;

class SampleUsageDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createSampleUsageLogs();
    }

    /**
     * Create sample usage logs for testing
     */
    private function createSampleUsageLogs()
    {
        // Get available models
        $models = AIModel::all();
        if ($models->isEmpty()) {
            $this->command->warn('No AI models found. Please run the AI providers seeder first.');

            return;
        }

        $modules = ['AICore', 'DocumentSummarizerAI', 'AutoDescriptionAI', 'HRAssistantAI'];
        $operations = ['chat', 'complete', 'summarize', 'extract', 'analyze'];
        $statuses = ['success', 'success', 'success', 'success', 'error']; // 80% success rate

        $this->command->info('Creating sample AI usage logs...');

        // Create logs for the last 30 days
        for ($i = 0; $i < 150; $i++) {
            $model = $models->random();
            $module = $modules[array_rand($modules)];
            $operation = $operations[array_rand($operations)];
            $status = $statuses[array_rand($statuses)];

            // Random date within last 30 days
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            // Generate realistic token counts based on operation
            $promptTokens = match ($operation) {
                'chat' => rand(100, 500),
                'complete' => rand(50, 200),
                'summarize' => rand(1000, 3000),
                'extract' => rand(500, 1500),
                'analyze' => rand(800, 2000),
                default => rand(100, 500)
            };

            $completionTokens = match ($operation) {
                'chat' => rand(50, 300),
                'complete' => rand(100, 800),
                'summarize' => rand(100, 500),
                'extract' => rand(50, 200),
                'analyze' => rand(200, 600),
                default => rand(50, 300)
            };

            $totalTokens = $promptTokens + $completionTokens;

            // Calculate cost based on model pricing
            $inputCost = ($model->cost_per_input_token ?? 0.00001) * $promptTokens;
            $outputCost = ($model->cost_per_output_token ?? 0.00002) * $completionTokens;
            $totalCost = $inputCost + $outputCost;

            // Random processing time
            $processingTime = $status === 'success' ? rand(500, 3000) : null;

            $errorMessage = $status === 'error' ? 'Rate limit exceeded' : null;

            AIUsageLog::create([
                'user_id' => 1, // Admin user
                'company_id' => 1, // Default company
                'module_name' => $module,
                'operation_type' => $operation,
                'model_id' => $model->id,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'cost' => $totalCost,
                'processing_time_ms' => $processingTime,
                'status' => $status,
                'error_message' => $errorMessage,
                'request_hash' => 'sample_'.md5($i.$model->id.$createdAt),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command->info('Created 150 sample AI usage logs');
    }
}
