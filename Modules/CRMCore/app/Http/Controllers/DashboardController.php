<?php

namespace Modules\CRMCore\app\Http\Controllers;

use App\ApiClasses\Success;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Deal;
use Modules\CRMCore\app\Models\DealPipeline;
use Modules\CRMCore\app\Models\DealStage;
use Modules\CRMCore\app\Models\Lead;
use Modules\CRMCore\app\Models\LeadStatus;
use Modules\CRMCore\app\Models\Task;
use Modules\CRMCore\app\Models\TaskStatus;

class DashboardController extends Controller
{
    /**
     * Display the CRM dashboard.
     */
    public function index()
    {
        // Get status IDs
        $qualifiedLeadStatus = LeadStatus::where('name', 'like', '%qualified%')->first();
        $wonDealStage = DealStage::where('name', 'like', '%won%')->orWhere('name', 'like', '%closed%')->first();
        $lostDealStage = DealStage::where('name', 'like', '%lost%')->first();
        $openDealStages = DealStage::whereNotIn('name', ['won', 'closed', 'lost'])->pluck('id');

        $pendingTaskStatus = TaskStatus::where('name', 'like', '%pending%')->orWhere('name', 'like', '%new%')->first();
        $completedTaskStatus = TaskStatus::where('name', 'like', '%completed%')->orWhere('name', 'like', '%done%')->first();

        // Statistics
        $statistics = [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_active', true)->count(),
            'total_contacts' => Contact::count(),
            'total_leads' => Lead::count(),
            'qualified_leads' => $qualifiedLeadStatus ? Lead::where('lead_status_id', $qualifiedLeadStatus->id)->count() : 0,
            'total_deals' => Deal::count(),
            'open_deals' => Deal::whereIn('deal_stage_id', $openDealStages)->count(),
            'won_deals' => $wonDealStage ? Deal::where('deal_stage_id', $wonDealStage->id)->count() : 0,
            'total_tasks' => Task::count(),
            'pending_tasks' => $pendingTaskStatus ? Task::where('task_status_id', $pendingTaskStatus->id)->count() : 0,
            'completed_tasks' => $completedTaskStatus ? Task::where('task_status_id', $completedTaskStatus->id)->count() : 0,
        ];

        // Revenue statistics
        $revenue = [
            'total_revenue' => $wonDealStage ? Deal::where('deal_stage_id', $wonDealStage->id)->sum('value') : 0,
            'potential_revenue' => Deal::whereIn('deal_stage_id', $openDealStages)->sum('value'),
            'average_deal_value' => $wonDealStage ? (Deal::where('deal_stage_id', $wonDealStage->id)->avg('value') ?? 0) : 0,
        ];

        // Recent activities
        $recentCompanies = Company::with('assignedToUser:id,first_name,last_name')
            ->latest()
            ->take(5)
            ->get();

        $recentContacts = Contact::with(['company:id,name', 'assignedToUser:id,first_name,last_name'])
            ->latest()
            ->take(5)
            ->get();

        $recentDeals = Deal::with(['contact:id,first_name,last_name', 'pipeline:id,name'])
            ->latest()
            ->take(5)
            ->get();

        // Upcoming tasks
        $upcomingTasks = Task::with(['taskable', 'assignedToUser:id,first_name,last_name', 'priority:id,name'])
            ->where(function ($query) use ($completedTaskStatus) {
                if ($completedTaskStatus) {
                    $query->where('task_status_id', '!=', $completedTaskStatus->id);
                }
            })
            ->whereNotNull('due_date')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Get pipelines for chart
        $pipelines = DealPipeline::withCount([
            'deals as deals_count' => function ($query) use ($openDealStages) {
                $query->whereIn('deal_stage_id', $openDealStages);
            },
        ])->get();

        return view('crmcore::dashboard.index', compact(
            'statistics',
            'revenue',
            'recentCompanies',
            'recentContacts',
            'recentDeals',
            'upcomingTasks',
            'pipelines'
        ));
    }

    /**
     * Get chart data for deals by month
     */
    public function getDealsChartData(Request $request)
    {
        $months = 6; // Last 6 months
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();

        // Get status IDs
        $wonDealStage = DealStage::where('name', 'like', '%won%')->orWhere('name', 'like', '%closed%')->first();
        $lostDealStage = DealStage::where('name', 'like', '%lost%')->first();

        $dealsData = Deal::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as total'),
            DB::raw('SUM(CASE WHEN deal_stage_id = '.($wonDealStage ? $wonDealStage->id : 0).' THEN 1 ELSE 0 END) as won'),
            DB::raw('SUM(CASE WHEN deal_stage_id = '.($lostDealStage ? $lostDealStage->id : 0).' THEN 1 ELSE 0 END) as lost'),
            DB::raw('SUM(CASE WHEN deal_stage_id = '.($wonDealStage ? $wonDealStage->id : 0).' THEN value ELSE 0 END) as revenue')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Fill in missing months
        $data = [];
        $currentMonth = $startDate->copy();

        while ($currentMonth <= $endDate) {
            $monthKey = $currentMonth->format('Y-m');
            $monthData = $dealsData->firstWhere('month', $monthKey);

            $data[] = [
                'month' => $currentMonth->format('M Y'),
                'total' => $monthData ? $monthData->total : 0,
                'won' => $monthData ? $monthData->won : 0,
                'lost' => $monthData ? $monthData->lost : 0,
                'revenue' => $monthData ? $monthData->revenue : 0,
            ];

            $currentMonth->addMonth();
        }

        return Success::response([
            'chart_data' => $data,
        ]);
    }

    /**
     * Get chart data for leads by source
     */
    public function getLeadsChartData(Request $request)
    {
        $leadsBySource = Lead::select('lead_sources.name as source', DB::raw('COUNT(leads.id) as count'))
            ->leftJoin('lead_sources', 'leads.lead_source_id', '=', 'lead_sources.id')
            ->groupBy('lead_sources.id', 'lead_sources.name')
            ->orderBy('count', 'desc')
            ->get();

        $data = $leadsBySource->map(function ($item) {
            return [
                'source' => $item->source ?? 'Unknown',
                'count' => $item->count,
            ];
        });

        return Success::response([
            'chart_data' => $data,
        ]);
    }

    /**
     * Get chart data for tasks by status
     */
    public function getTasksChartData(Request $request)
    {
        $tasksByStatus = Task::select('task_statuses.name as status', DB::raw('COUNT(crm_tasks.id) as count'))
            ->leftJoin('task_statuses', 'crm_tasks.task_status_id', '=', 'task_statuses.id')
            ->groupBy('task_statuses.id', 'task_statuses.name')
            ->get();

        $data = $tasksByStatus->map(function ($item) {
            return [
                'status' => $item->status ?? 'Unknown',
                'count' => $item->count,
            ];
        });

        return Success::response([
            'chart_data' => $data,
        ]);
    }
}
