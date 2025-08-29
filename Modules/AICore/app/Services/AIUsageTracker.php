<?php

namespace Modules\AICore\Services;

use App\Services\Settings\ModuleSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\AICore\Models\AIUsageLog;

class AIUsageTracker
{
    /**
     * Log AI usage with all relevant metrics
     */
    public function logUsage(array $data): AIUsageLog
    {
        try {
            // Log the attempt for debugging
            Log::info('Attempting to log AI usage', [
                'module' => $data['module_name'] ?? 'unknown',
                'status' => $data['status'] ?? 'unknown',
                'has_error' => isset($data['error_message']),
            ]);

            $usageLog = AIUsageLog::create([
                'user_id' => $data['user_id'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'module_name' => $data['module_name'],
                'operation_type' => $data['operation_type'],
                'model_id' => $data['model_id'],
                'prompt_tokens' => $data['prompt_tokens'] ?? 0,
                'completion_tokens' => $data['completion_tokens'] ?? 0,
                'total_tokens' => $data['total_tokens'] ?? 0,
                'cost' => $data['cost'] ?? 0,
                'processing_time_ms' => $data['processing_time_ms'] ?? null,
                'status' => $data['status'] ?? 'success',
                'error_message' => $data['error_message'] ?? null,
                'request_hash' => $data['request_hash'] ?? null,
            ]);

            Log::info('AI usage logged successfully', [
                'id' => $usageLog->id,
                'module' => $data['module_name'],
                'status' => $data['status'] ?? 'success',
            ]);

            // Check quota limits after logging (disabled for single company app)
            // if ($data['company_id']) {
            //     $this->checkQuotaLimits($data['company_id'], $data['module_name']);
            // }

            return $usageLog;

        } catch (\Exception $e) {
            Log::error('Failed to log AI usage', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get current usage for a company and module
     */
    public function getCurrentUsage(?int $companyId = null, ?string $moduleName = null, string $period = 'daily'): array
    {
        $query = AIUsageLog::query();

        // Only filter by company if provided (for backwards compatibility)
        if ($companyId !== null) {
            $query->forCompany($companyId);
        }

        if ($moduleName) {
            $query->forModule($moduleName);
        }

        // Set date range based on period
        switch ($period) {
            case 'hourly':
                $query->where('created_at', '>=', now()->subHour());
                break;
            case 'daily':
                $query->where('created_at', '>=', now()->subDay());
                break;
            case 'weekly':
                $query->where('created_at', '>=', now()->subWeek());
                break;
            case 'monthly':
                $query->where('created_at', '>=', now()->subMonth());
                break;
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            SUM(total_tokens) as total_tokens,
            SUM(cost) as total_cost,
            AVG(processing_time_ms) as avg_processing_time,
            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_requests
        ')->first();

        return [
            'company_id' => $companyId ?? 'all',
            'module_name' => $moduleName,
            'period' => $period,
            'total_requests' => $stats->total_requests ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => round($stats->total_cost ?? 0, 2),
            'avg_processing_time' => round($stats->avg_processing_time ?? 0, 2),
            'successful_requests' => $stats->successful_requests ?? 0,
            'success_rate' => $stats->total_requests > 0
                ? round(($stats->successful_requests / $stats->total_requests) * 100, 2)
                : 0,
        ];
    }

    /**
     * Check if company has exceeded quota limits
     */
    public function checkQuotaLimits(int $companyId, string $moduleName): array
    {
        $dailyUsage = $this->getCurrentUsage($companyId, $moduleName, 'daily');
        $monthlyUsage = $this->getCurrentUsage($companyId, $moduleName, 'monthly');

        // Get quota configurations from settings
        $settingsService = app(ModuleSettingsService::class);
        $dailyTokenLimit = $settingsService->get('AICore', 'aicore.daily_token_limit', 100000);
        $monthlyCostLimit = $settingsService->get('AICore', 'aicore.monthly_budget', 100);
        $dailyRequestLimit = $settingsService->get('AICore', 'aicore.user_rate_limit', 20) * 60 * 24; // Convert per minute to per day

        $violations = [];

        // Check daily token limit
        if ($dailyUsage['total_tokens'] > $dailyTokenLimit) {
            $violations[] = [
                'type' => 'daily_tokens',
                'current' => $dailyUsage['total_tokens'],
                'limit' => $dailyTokenLimit,
                'percentage' => round(($dailyUsage['total_tokens'] / $dailyTokenLimit) * 100, 1),
            ];
        }

        // Check monthly cost limit
        if ($monthlyUsage['total_cost'] > $monthlyCostLimit) {
            $violations[] = [
                'type' => 'monthly_cost',
                'current' => $monthlyUsage['total_cost'],
                'limit' => $monthlyCostLimit,
                'percentage' => round(($monthlyUsage['total_cost'] / $monthlyCostLimit) * 100, 1),
            ];
        }

        // Check daily request limit
        if ($dailyUsage['total_requests'] > $dailyRequestLimit) {
            $violations[] = [
                'type' => 'daily_requests',
                'current' => $dailyUsage['total_requests'],
                'limit' => $dailyRequestLimit,
                'percentage' => round(($dailyUsage['total_requests'] / $dailyRequestLimit) * 100, 1),
            ];
        }

        return [
            'quota_exceeded' => ! empty($violations),
            'violations' => $violations,
            'daily_usage' => $dailyUsage,
            'monthly_usage' => $monthlyUsage,
        ];
    }

    /**
     * Generate usage report for a specific period
     */
    public function generateUsageReport(?int $companyId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $query = AIUsageLog::dateRange($start, $end);

        // Only filter by company if provided
        if ($companyId !== null) {
            $query->forCompany($companyId);
        }

        $logs = $query
            ->with(['model.provider'])
            ->get();

        // Overall statistics
        $overallStats = [
            'total_requests' => $logs->count(),
            'total_tokens' => $logs->sum('total_tokens'),
            'total_cost' => round($logs->sum('cost'), 2),
            'avg_processing_time' => round($logs->avg('processing_time_ms'), 2),
            'successful_requests' => $logs->where('status', 'success')->count(),
            'success_rate' => $logs->count() > 0
                ? round(($logs->where('status', 'success')->count() / $logs->count()) * 100, 2)
                : 0,
        ];

        // Usage by module
        $moduleStats = $logs->groupBy('module_name')->map(function ($moduleLogs, $moduleName) {
            return [
                'module_name' => $moduleName,
                'total_requests' => $moduleLogs->count(),
                'total_tokens' => $moduleLogs->sum('total_tokens'),
                'total_cost' => round($moduleLogs->sum('cost'), 2),
                'avg_processing_time' => round($moduleLogs->avg('processing_time_ms'), 2),
                'success_rate' => $moduleLogs->count() > 0
                    ? round(($moduleLogs->where('status', 'success')->count() / $moduleLogs->count()) * 100, 2)
                    : 0,
            ];
        })->values();

        // Usage by provider
        $providerStats = $logs->groupBy('model.provider.name')->map(function ($providerLogs, $providerName) {
            return [
                'provider_name' => $providerName,
                'total_requests' => $providerLogs->count(),
                'total_tokens' => $providerLogs->sum('total_tokens'),
                'total_cost' => round($providerLogs->sum('cost'), 2),
                'avg_processing_time' => round($providerLogs->avg('processing_time_ms'), 2),
                'success_rate' => $providerLogs->count() > 0
                    ? round(($providerLogs->where('status', 'success')->count() / $providerLogs->count()) * 100, 2)
                    : 0,
            ];
        })->values();

        // Daily breakdown
        $dailyStats = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        })->map(function ($dayLogs, $date) {
            return [
                'date' => $date,
                'total_requests' => $dayLogs->count(),
                'total_tokens' => $dayLogs->sum('total_tokens'),
                'total_cost' => round($dayLogs->sum('cost'), 2),
                'success_rate' => $dayLogs->count() > 0
                    ? round(($dayLogs->where('status', 'success')->count() / $dayLogs->count()) * 100, 2)
                    : 0,
            ];
        })->values();

        return [
            'report_period' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'days' => $start->diffInDays($end) + 1,
            ],
            'overall_stats' => $overallStats,
            'module_breakdown' => $moduleStats,
            'provider_breakdown' => $providerStats,
            'daily_breakdown' => $dailyStats,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get top performing models
     */
    public function getTopModels(?int $companyId, int $days = 30, int $limit = 10): array
    {
        $query = AIUsageLog::recent($days);

        // Only filter by company if provided
        if ($companyId !== null) {
            $query->forCompany($companyId);
        }

        $stats = $query
            ->successful()
            ->with(['model.provider'])
            ->selectRaw('
                model_id,
                COUNT(*) as total_requests,
                SUM(total_tokens) as total_tokens,
                SUM(cost) as total_cost,
                AVG(processing_time_ms) as avg_processing_time
            ')
            ->groupBy('model_id')
            ->orderBy('total_requests', 'desc')
            ->limit($limit)
            ->get();

        return $stats->map(function ($stat) {
            return [
                'model_name' => $stat->model->name ?? 'Unknown',
                'provider_name' => $stat->model->provider->name ?? 'Unknown',
                'total_requests' => $stat->total_requests,
                'total_tokens' => $stat->total_tokens,
                'total_cost' => round($stat->total_cost, 2),
                'avg_processing_time' => round($stat->avg_processing_time, 2),
                'efficiency_score' => $stat->avg_processing_time > 0
                    ? round($stat->total_tokens / $stat->avg_processing_time, 2)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get cost trend analysis
     */
    public function getCostTrends(?int $companyId, int $days = 30): array
    {
        $query = AIUsageLog::recent($days);

        // Only filter by company if provided
        if ($companyId !== null) {
            $query->forCompany($companyId);
        }

        $logs = $query
            ->successful()
            ->get();

        $dailyCosts = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        })->map(function ($dayLogs) {
            return round($dayLogs->sum('cost'), 2);
        });

        $avgDailyCost = $dailyCosts->avg();
        $totalCost = $dailyCosts->sum();
        $trend = $this->calculateTrend($dailyCosts->values()->toArray());

        return [
            'period_days' => $days,
            'total_cost' => round($totalCost, 2),
            'avg_daily_cost' => round($avgDailyCost, 2),
            'trend_direction' => $trend['direction'],
            'trend_percentage' => $trend['percentage'],
            'daily_costs' => $dailyCosts->toArray(),
            'projected_monthly_cost' => round($avgDailyCost * 30, 2),
        ];
    }

    /**
     * Calculate trend direction and percentage
     */
    protected function calculateTrend(array $values): array
    {
        if (count($values) < 2) {
            return ['direction' => 'stable', 'percentage' => 0];
        }

        $firstHalf = array_slice($values, 0, intval(count($values) / 2));
        $secondHalf = array_slice($values, intval(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0) {
            return ['direction' => 'stable', 'percentage' => 0];
        }

        $percentage = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        if (abs($percentage) < 5) {
            $direction = 'stable';
        } elseif ($percentage > 0) {
            $direction = 'increasing';
        } else {
            $direction = 'decreasing';
        }

        return [
            'direction' => $direction,
            'percentage' => round(abs($percentage), 1),
        ];
    }
}
