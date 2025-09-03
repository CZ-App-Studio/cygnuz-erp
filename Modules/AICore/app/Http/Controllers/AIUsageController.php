<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\AICore\Models\AIModuleConfiguration;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Models\AIUsageLog;
use Modules\AICore\Services\AIUsageTracker;

class AIUsageController extends Controller
{
    protected AIUsageTracker $usageTracker;

    public function __construct(AIUsageTracker $usageTracker)
    {
        $this->usageTracker = $usageTracker;
    }

    /**
     * Display AI usage analytics dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 30); // days
        $providerId = $request->get('provider', '');
        $moduleName = $request->get('module', '');

        // Get all active providers for the filter dropdown
        $providers = AIProvider::active()->orderBy('name')->get();

        // Get all AI modules for the filter dropdown
        $modules = AIModuleConfiguration::active()
            ->ordered()
            ->get(['module_name', 'module_display_name']);

        // Get summary statistics
        $summary = $this->getSummaryStats($period, $providerId, $moduleName);

        // Get top models by usage
        $topModels = $this->getTopModels($period, $providerId, $moduleName);

        // Get chart data for trends
        $chartData = $this->getChartData($period, $providerId, $moduleName);

        // Get recent logs for the table
        $recentLogs = $this->getRecentLogs($period, $providerId, $moduleName);

        return view('aicore::usage.index', compact(
            'summary',
            'topModels',
            'chartData',
            'recentLogs',
            'providers',
            'modules',
            'period',
            'providerId',
            'moduleName'
        ));
    }

    /**
     * Display detailed information for a specific usage log
     */
    public function show($id)
    {
        $log = AIUsageLog::with(['model.provider', 'user'])->findOrFail($id);

        // Calculate additional metrics
        $metrics = [
            'tokens_per_second' => $log->tokens_per_second,
            'cost_per_token' => $log->cost_per_token,
            'prompt_percentage' => $log->total_tokens > 0
                ? round(($log->prompt_tokens / $log->total_tokens) * 100, 1)
                : 0,
            'completion_percentage' => $log->total_tokens > 0
                ? round(($log->completion_tokens / $log->total_tokens) * 100, 1)
                : 0,
        ];

        // Get related logs (same module and operation in the last 24 hours)
        $relatedLogs = AIUsageLog::where('module_name', $log->module_name)
            ->where('operation_type', $log->operation_type)
            ->where('id', '!=', $id)
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Get statistics for comparison
        $comparisonStats = AIUsageLog::where('module_name', $log->module_name)
            ->where('operation_type', $log->operation_type)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('
                AVG(total_tokens) as avg_tokens,
                AVG(cost) as avg_cost,
                AVG(processing_time_ms) as avg_processing_time,
                MIN(processing_time_ms) as min_processing_time,
                MAX(processing_time_ms) as max_processing_time
            ')
            ->first();

        if (request()->ajax()) {
            return response()->json([
                'log' => $log,
                'metrics' => $metrics,
                'relatedLogs' => $relatedLogs,
                'comparisonStats' => $comparisonStats,
            ]);
        }

        return view('aicore::usage.show', compact('log', 'metrics', 'relatedLogs', 'comparisonStats'));
    }

    /**
     * Export usage data
     */
    public function export(Request $request)
    {
        $period = $request->get('period', 30);
        $providerId = $request->get('provider', '');

        $startDate = Carbon::now()->subDays($period)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Generate comprehensive usage report
        $report = $this->usageTracker->generateUsageReport(
            null,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        $fileName = 'ai-usage-report-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.json';

        return response()->json($report)
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"')
            ->header('Content-Type', 'application/json');
    }

    /**
     * Get summary statistics
     */
    protected function getSummaryStats(int $period, string $providerId, string $moduleName = ''): array
    {
        $query = AIUsageLog::where('ai_usage_logs.created_at', '>=', Carbon::now()->subDays($period));

        if ($providerId) {
            $query->join('ai_models', 'ai_usage_logs.model_id', '=', 'ai_models.id')
                ->where('ai_models.provider_id', $providerId);
        }

        if ($moduleName) {
            $query->where('ai_usage_logs.module_name', $moduleName);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            SUM(total_tokens) as total_tokens,
            SUM(cost) as total_cost,
            AVG(processing_time_ms) as avg_response_time,
            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_requests
        ')->first();

        return [
            'total_requests' => $stats->total_requests ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => round($stats->total_cost ?? 0, 2),
            'avg_response_time' => round($stats->avg_response_time ?? 0, 0),
            'success_rate' => $stats->total_requests > 0
                ? round(($stats->successful_requests / $stats->total_requests) * 100, 1)
                : 0,
        ];
    }

    /**
     * Get top models by usage
     */
    protected function getTopModels(int $period, string $providerId, string $moduleName = ''): array
    {
        $query = AIUsageLog::join('ai_models', 'ai_usage_logs.model_id', '=', 'ai_models.id')
            ->join('ai_providers', 'ai_models.provider_id', '=', 'ai_providers.id')
            ->where('ai_usage_logs.created_at', '>=', Carbon::now()->subDays($period));

        if ($providerId) {
            $query->where('ai_providers.id', $providerId);
        }

        if ($moduleName) {
            $query->where('ai_usage_logs.module_name', $moduleName);
        }

        return $query->selectRaw('
            ai_models.name as model_name,
            ai_providers.name as provider_name,
            COUNT(*) as total_requests,
            SUM(ai_usage_logs.cost) as total_cost,
            SUM(ai_usage_logs.total_tokens) as total_tokens
        ')
            ->groupBy('ai_models.id', 'ai_models.name', 'ai_providers.name')
            ->orderByDesc('total_requests')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get chart data for analytics
     */
    protected function getChartData(int $period, string $providerId, string $moduleName = ''): array
    {
        // Usage trends over time
        $usageTrends = $this->getUsageTrends($period, $providerId, $moduleName);

        // Module usage breakdown
        $moduleUsage = $this->getModuleUsage($period, $providerId, $moduleName);

        // Provider cost breakdown
        $providerCost = $this->getProviderCost($period, $providerId, $moduleName);

        return [
            'usage_trends' => $usageTrends,
            'module_usage' => $moduleUsage,
            'provider_cost' => $providerCost,
        ];
    }

    /**
     * Get usage trends over time
     */
    protected function getUsageTrends(int $period, string $providerId, string $moduleName = ''): array
    {
        $query = AIUsageLog::where('ai_usage_logs.created_at', '>=', Carbon::now()->subDays($period));

        if ($providerId) {
            $query->join('ai_models', 'ai_usage_logs.model_id', '=', 'ai_models.id')
                ->where('ai_models.provider_id', $providerId);
        }

        if ($moduleName) {
            $query->where('ai_usage_logs.module_name', $moduleName);
        }

        $trends = $query->selectRaw('
            DATE(ai_usage_logs.created_at) as date,
            COUNT(*) as requests,
            SUM(total_tokens) as tokens,
            SUM(cost) as cost,
            AVG(processing_time_ms) as avg_response_time
        ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $trends->map(function ($trend) {
            return [
                'date' => $trend->date,
                'requests' => $trend->requests,
                'tokens' => $trend->tokens,
                'cost' => round($trend->cost, 2),
                'response_time' => round($trend->avg_response_time, 0),
            ];
        })->toArray();
    }

    /**
     * Get module usage breakdown
     */
    protected function getModuleUsage(int $period, string $providerId, string $moduleName = ''): array
    {
        $query = AIUsageLog::where('ai_usage_logs.created_at', '>=', Carbon::now()->subDays($period));

        if ($providerId) {
            $query->join('ai_models', 'ai_usage_logs.model_id', '=', 'ai_models.id')
                ->where('ai_models.provider_id', $providerId);
        }

        if ($moduleName) {
            $query->where('ai_usage_logs.module_name', $moduleName);
        }

        return $query->selectRaw('
            module_name,
            COUNT(*) as total_requests,
            SUM(cost) as total_cost
        ')
            ->groupBy('module_name')
            ->orderByDesc('total_requests')
            ->get()
            ->map(function ($usage) {
                return [
                    'module' => $usage->module_name,
                    'requests' => $usage->total_requests,
                    'cost' => round($usage->total_cost, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get provider cost breakdown
     */
    protected function getProviderCost(int $period, string $providerId, string $moduleName = ''): array
    {
        $query = AIUsageLog::join('ai_models', 'ai_usage_logs.model_id', '=', 'ai_models.id')
            ->join('ai_providers', 'ai_models.provider_id', '=', 'ai_providers.id')
            ->where('ai_usage_logs.created_at', '>=', Carbon::now()->subDays($period));

        if ($providerId) {
            $query->where('ai_providers.id', $providerId);
        }

        if ($moduleName) {
            $query->where('ai_usage_logs.module_name', $moduleName);
        }

        return $query->selectRaw('
            ai_providers.name as provider_name,
            COUNT(*) as total_requests,
            SUM(ai_usage_logs.cost) as total_cost
        ')
            ->groupBy('ai_providers.id', 'ai_providers.name')
            ->orderByDesc('total_cost')
            ->get()
            ->map(function ($provider) {
                return [
                    'provider' => $provider->provider_name,
                    'requests' => $provider->total_requests,
                    'cost' => round($provider->total_cost, 2),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent logs for the table
     */
    protected function getRecentLogs(int $period, string $providerId, string $moduleName = ''): array
    {
        $query = AIUsageLog::join('ai_models', 'ai_usage_logs.model_id', '=', 'ai_models.id')
            ->join('ai_providers', 'ai_models.provider_id', '=', 'ai_providers.id')
            ->where('ai_usage_logs.created_at', '>=', Carbon::now()->subDays($period));

        if ($providerId) {
            $query->where('ai_providers.id', $providerId);
        }

        if ($moduleName) {
            $query->where('ai_usage_logs.module_name', $moduleName);
        }

        $results = $query->select([
            'ai_usage_logs.id',
            'ai_usage_logs.created_at',
            'ai_usage_logs.module_name',
            'ai_usage_logs.operation_type',
            'ai_usage_logs.total_tokens',
            'ai_usage_logs.cost',
            'ai_usage_logs.processing_time_ms',
            'ai_usage_logs.status',
            'ai_models.name as model_name',
            'ai_providers.name as provider_name',
        ])
            ->orderByDesc('ai_usage_logs.created_at')
            ->limit(50)
            ->get();

        // Convert to array while preserving Carbon instances
        return $results->map(function ($log) {
            return [
                'id' => $log->id,
                'created_at' => $log->created_at, // Keep as Carbon instance
                'module_name' => $log->module_name,
                'operation_type' => $log->operation_type,
                'total_tokens' => $log->total_tokens,
                'cost' => $log->cost,
                'processing_time_ms' => $log->processing_time_ms,
                'status' => $log->status,
                'model_name' => $log->model_name,
                'provider_name' => $log->provider_name,
            ];
        })->toArray();
    }
}
