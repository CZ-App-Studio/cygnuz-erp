<?php

namespace Modules\AccountingCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\AddonService\AddonService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\AccountingCore\app\Models\BasicTransaction;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:accountingcore.dashboard.view')->only(['index']);
        $this->middleware('permission:accountingcore.dashboard.statistics')->only(['statistics']);
    }

    /**
     * Display the accounting dashboard.
     */
    public function index(Request $request)
    {
        // Check if AccountingPro is enabled
        $addonService = app(AddonService::class);
        if ($addonService->isAddonEnabled('AccountingPro')) {
            return redirect()->route('accountingpro.dashboard');
        }

        // Get date range from request or default to current month
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : now()->endOfMonth();

        // Get summary statistics
        $summary = BasicTransaction::getSummaryForPeriod($startDate, $endDate);

        // Get running balance
        $runningBalance = BasicTransaction::getRunningBalance($endDate);

        // Get recent transactions
        $recentTransactions = BasicTransaction::with(['category', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get top expense categories for the period (for donut chart)
        $topCategories = BasicTransaction::expense()
            ->forDateRange($startDate, $endDate)
            ->selectRaw('category_id, SUM(amount) as total, COUNT(*) as transaction_count')
            ->groupBy('category_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->with('category')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'name' => $item->category ? $item->category->name : 'Unknown',
                    'total' => floatval($item->total),
                    'transaction_count' => intval($item->transaction_count),
                ];
            });

        // Get chart data for income vs expense
        $chartData = $this->getMonthlyTrend();

        // Breadcrumb data
        $breadcrumbs = [
            ['name' => __('Accounting'), 'url' => route('accountingcore.dashboard')],
            ['name' => __('Dashboard'), 'url' => ''],
        ];

        return view('accountingcore::dashboard.index', compact(
            'summary',
            'runningBalance',
            'recentTransactions',
            'topCategories',
            'chartData',
            'startDate',
            'endDate',
            'breadcrumbs'
        ));
    }

    /**
     * Get monthly trend data for the last 12 months.
     */
    private function getMonthlyTrend(): array
    {
        $months = collect();
        $incomeData = [];
        $expenseData = [];
        $labels = [];

        // Show only last 6 months for cleaner display
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $monthSummary = BasicTransaction::getSummaryForPeriod($startOfMonth, $endOfMonth);

            $labels[] = $month->format('M');
            $incomeData[] = floatval($monthSummary['income']);
            $expenseData[] = floatval($monthSummary['expense']);
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expenses' => $expenseData,
        ];
    }

    /**
     * Get dashboard statistics via AJAX.
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 'this_month');

        switch ($period) {
            case 'last_month':
                $startDate = now()->subMonth()->startOfMonth();
                $endDate = now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            default: // this_month
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
        }

        // Get chart data for the selected period
        $chartData = $this->getChartDataForPeriod($startDate, $endDate);

        return response()->json([
            'success' => true,
            'chartData' => $chartData,
        ]);
    }

    /**
     * Get chart data for a specific period.
     */
    private function getChartDataForPeriod($startDate, $endDate)
    {
        $incomeData = [];
        $expenseData = [];
        $labels = [];

        // Determine the interval based on date range
        $diffInDays = $startDate->diffInDays($endDate);

        if ($diffInDays <= 31) {
            // Daily data for month view
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $dayStart = $current->copy()->startOfDay();
                $dayEnd = $current->copy()->endOfDay();

                $daySummary = BasicTransaction::getSummaryForPeriod($dayStart, $dayEnd);

                $labels[] = $current->format('d');
                $incomeData[] = $daySummary['income'];
                $expenseData[] = $daySummary['expense'];

                $current->addDay();
            }
        } else {
            // Monthly data for year view
            $current = $startDate->copy()->startOfMonth();
            while ($current <= $endDate) {
                $monthStart = $current->copy()->startOfMonth();
                $monthEnd = $current->copy()->endOfMonth();

                if ($monthEnd > $endDate) {
                    $monthEnd = $endDate;
                }

                $monthSummary = BasicTransaction::getSummaryForPeriod($monthStart, $monthEnd);

                $labels[] = $current->format('M');
                $incomeData[] = $monthSummary['income'];
                $expenseData[] = $monthSummary['expense'];

                $current->addMonth();
            }
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expenses' => $expenseData,
        ];
    }
}
