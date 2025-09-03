<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Models\AIUsageLog;
use Modules\AICore\Services\AIProviderService;
use Modules\AICore\Services\AIUsageTracker;

class AIDashboardController extends Controller
{
    protected AIUsageTracker $usageTracker;

    protected AIProviderService $providerService;

    public function __construct(AIUsageTracker $usageTracker, AIProviderService $providerService)
    {
        $this->usageTracker = $usageTracker;
        $this->providerService = $providerService;
    }

    /**
     * Display the AI Core dashboard
     */
    public function index(Request $request)
    {
        // Get overview statistics
        $overviewStats = $this->getOverviewStatistics();

        // Get provider status
        $providerStatus = $this->getProviderStatus();

        // Get recent usage
        $recentUsage = $this->getRecentUsage();

        // Get cost trends
        $costTrends = $this->usageTracker->getCostTrends(null, 30);

        // Get top models
        $topModels = $this->usageTracker->getTopModels(null, 30, 5);

        return view('aicore::dashboard.index', compact(
            'overviewStats',
            'providerStatus',
            'recentUsage',
            'costTrends',
            'topModels'
        ));
    }

    /**
     * Get overview statistics for the dashboard
     */
    private function getOverviewStatistics(): array
    {
        // Total providers and models
        $totalProviders = AIProvider::active()->count();
        $totalModels = AIModel::active()->count();

        // Usage statistics for different periods
        $todayUsage = $this->usageTracker->getCurrentUsage(null, null, 'daily');
        $weekUsage = $this->usageTracker->getCurrentUsage(null, null, 'weekly');
        $monthUsage = $this->usageTracker->getCurrentUsage(null, null, 'monthly');

        return [
            'total_providers' => $totalProviders,
            'total_models' => $totalModels,
            'today' => $todayUsage,
            'week' => $weekUsage,
            'month' => $monthUsage,
        ];
    }

    /**
     * Get provider connection status
     */
    private function getProviderStatus(): array
    {
        $providers = AIProvider::active()->get();
        $status = [];

        foreach ($providers as $provider) {
            // For now, just return basic status without actual connection testing
            // Connection testing will be implemented when providers have valid API keys
            $status[] = [
                'id' => $provider->id,
                'name' => $provider->name,
                'type' => $provider->type,
                'is_connected' => $provider->api_key_encrypted ? true : false,
                'response_time' => 'N/A',
                'error_message' => $provider->api_key_encrypted ? null : 'No API key configured',
                'models_count' => $provider->models()->count(),
            ];
        }

        return $status;
    }

    /**
     * Get recent usage activity
     */
    private function getRecentUsage(): array
    {
        $recentLogs = AIUsageLog::with(['model.provider'])
            ->recent(7)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return $recentLogs->map(function ($log) {
            return [
                'id' => $log->id,
                'module_name' => $log->module_name,
                'operation_type' => $log->operation_type,
                'model_name' => $log->model->name ?? 'Unknown',
                'provider_name' => $log->model->provider->name ?? 'Unknown',
                'total_tokens' => $log->total_tokens,
                'cost' => $log->cost,
                'processing_time' => $log->processing_time_ms,
                'status' => $log->status,
                'created_at' => $log->created_at,
            ];
        })->toArray();
    }

    /**
     * API endpoint for dashboard data refresh
     */
    public function getData(Request $request)
    {
        $type = $request->input('type', 'overview');

        switch ($type) {
            case 'overview':
                $data = $this->getOverviewStatistics();
                break;

            case 'providers':
                $data = $this->getProviderStatus();
                break;

            case 'usage':
                $data = $this->getRecentUsage();
                break;

            case 'trends':
                $days = $request->input('days', 30);
                $data = $this->usageTracker->getCostTrends(null, $days);
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data type requested',
                ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
