<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SettingHistory;
use App\Services\Settings\ModuleSettingsService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class SettingsHistoryController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected ModuleSettingsService $moduleSettings
    ) {}

    /**
     * Display settings history
     */
    public function index(Request $request): View
    {
        $query = SettingHistory::with('user')
            ->orderBy('changed_at', 'desc');

        // Apply filters
        if ($request->has('type')) {
            $query->where('setting_type', $request->get('type'));
        }

        if ($request->has('module')) {
            $query->where('module', $request->get('module'));
        }

        if ($request->has('user')) {
            $query->where('changed_by', $request->get('user'));
        }

        if ($request->has('from_date')) {
            $query->whereDate('changed_at', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('changed_at', '<=', $request->get('to_date'));
        }

        $history = $query->paginate(10);

        return view('settings.history.index', compact('history'));
    }

    /**
     * Get history for specific setting
     */
    public function getSettingHistory(string $key): JsonResponse
    {
        $history = SettingHistory::with('user')
            ->where('setting_key', $key)
            ->orderBy('changed_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Rollback to previous value
     */
    public function rollback(int $historyId): JsonResponse
    {
        $history = SettingHistory::findOrFail($historyId);

        try {
            DB::beginTransaction();

            if ($history->setting_type === 'system') {
                $this->settingsService->set($history->setting_key, $history->old_value);
            } else {
                $this->moduleSettings->set($history->module, $history->setting_key, $history->old_value);
            }

            // Log the rollback
            SettingHistory::create([
                'setting_type' => $history->setting_type,
                'setting_key' => $history->setting_key,
                'module' => $history->module,
                'old_value' => $history->new_value,
                'new_value' => $history->old_value,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('Setting rolled back successfully'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error rolling back setting: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('Failed to rollback setting'),
            ], 500);
        }
    }

    /**
     * Export history
     */
    public function export(Request $request): JsonResponse
    {
        $query = SettingHistory::with('user');

        // Apply same filters as index
        if ($request->has('type')) {
            $query->where('setting_type', $request->get('type'));
        }

        if ($request->has('module')) {
            $query->where('module', $request->get('module'));
        }

        if ($request->has('from_date')) {
            $query->whereDate('changed_at', '>=', $request->get('from_date'));
        }

        if ($request->has('to_date')) {
            $query->whereDate('changed_at', '<=', $request->get('to_date'));
        }

        $history = $query->orderBy('changed_at', 'desc')->get();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'filters' => $request->only(['type', 'module', 'from_date', 'to_date']),
            'history' => $history->map(function ($item) {
                return [
                    'type' => $item->setting_type,
                    'key' => $item->setting_key,
                    'module' => $item->module,
                    'old_value' => $item->old_value,
                    'new_value' => $item->new_value,
                    'changed_by' => $item->user?->name ?? 'Unknown',
                    'changed_at' => $item->changed_at->toIso8601String(),
                    'ip_address' => $item->ip_address,
                ];
            }),
        ];

        return response()->json($data)
            ->header('Content-Type', 'application/json')
            ->header('Content-Disposition', 'attachment; filename="settings-history-'.date('Y-m-d-His').'.json"');
    }
}
