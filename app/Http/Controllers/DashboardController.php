<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\AddonService\AddonService;

class DashboardController extends Controller
{
  protected AddonService $addonService;

  public function __construct(AddonService $addonService)
  {
    $this->addonService = $addonService;
  }

  /**
   * Display the appropriate dashboard based on user role
   */
  public function index()
  {
    $user = Auth::user();
    $role = $user->roles()->first();

    // Get the primary role name
    $roleName = $role ? $role->name : 'employee';

    // Determine which dashboard to show based on role
    switch ($roleName) {
      case 'tenant':
        $isMultiTenancyEnabled = $this->addonService->isAddonEnabled('MultiTenancyCore');
        if ($isMultiTenancyEnabled) {
          return redirect()->route('multitenancycore.tenant.dashboard');
        } else {
          return redirect()->route('login')->with('error', 'Multi-tenancy is not enabled.');
        }
      case 'super_admin':
      case 'admin':
        return $this->adminDashboard();

      case 'hr_manager':
      case 'hr_executive':
        return $this->hrDashboard();

      case 'accounting_manager':
      case 'accounting_executive':
        return $this->accountingDashboard();

      case 'crm_manager':
        return $this->crmDashboard();

      case 'project_manager':
        return $this->projectDashboard();

      case 'inventory_manager':
        return $this->inventoryDashboard();

      case 'sales_manager':
      case 'sales_executive':
        return $this->salesDashboard();

      case 'team_leader':
        return $this->teamLeaderDashboard();

      case 'field_employee':
        return $this->fieldEmployeeDashboard();

      case 'client':
        return $this->clientDashboard();

      default:
        return $this->employeeDashboard();
    }
  }

  /**
   * Admin Dashboard - Full system overview
   */
  protected function adminDashboard()
  {
    $data = [
      'pageTitle' => __('Admin Dashboard'),
      'stats' => $this->getAdminStats(),
      'recentActivities' => $this->getRecentActivities(),
      'systemHealth' => $this->getSystemHealth(),
      'enabledAddons' => $this->getEnabledAddons(),
      'addonWidgets' => $this->getAddonWidgets('admin'),
    ];

    return view('dashboards.admin', $data);
  }

  /**
   * HR Dashboard - HR specific metrics
   */
  protected function hrDashboard()
  {
    $data = [
      'pageTitle' => __('HR Dashboard'),
      'employeeStats' => $this->getEmployeeStats(),
      'attendanceOverview' => $this->getAttendanceOverview(),
      'leaveRequests' => $this->getPendingLeaveRequests(),
      'upcomingHolidays' => $this->getUpcomingHolidays(),
      'enabledAddons' => $this->getEnabledAddons(),
      'addonWidgets' => $this->getAddonWidgets('hr'),
    ];

    return view('dashboards.hr', $data);
  }

  /**
   * Accounting Dashboard - Financial overview
   */
  protected function accountingDashboard()
  {
    $data = [
      'pageTitle' => __('Accounting Dashboard'),
      'financialOverview' => $this->getFinancialOverview(),
      'pendingInvoices' => $this->getPendingInvoices(),
      'expenseOverview' => $this->getExpenseOverview(),
      'cashFlow' => $this->getCashFlowData(),
    ];

    return view('dashboards.accounting', $data);
  }

  /**
   * CRM Dashboard - Customer relationship metrics
   */
  protected function crmDashboard()
  {
    $data = [
      'pageTitle' => __('CRM Dashboard'),
      'leadStats' => $this->getLeadStats(),
      'dealPipeline' => $this->getDealPipeline(),
      'recentActivities' => $this->getCRMActivities(),
      'taskOverview' => $this->getTaskOverview(),
    ];

    return view('dashboards.crm', $data);
  }

  /**
   * Project Dashboard - Project management overview
   */
  protected function projectDashboard()
  {
    $data = [
      'pageTitle' => __('Project Dashboard'),
      'projectStats' => $this->getProjectStats(),
      'taskProgress' => $this->getTaskProgress(),
      'teamPerformance' => $this->getTeamPerformance(),
      'upcomingDeadlines' => $this->getUpcomingDeadlines(),
    ];

    return view('dashboards.project', $data);
  }

  /**
   * Inventory Dashboard - Stock and warehouse overview
   */
  protected function inventoryDashboard()
  {
    $data = [
      'pageTitle' => __('Inventory Dashboard'),
      'stockOverview' => $this->getStockOverview(),
      'lowStockAlerts' => $this->getLowStockAlerts(),
      'warehouseUtilization' => $this->getWarehouseUtilization(),
      'recentTransactions' => $this->getRecentInventoryTransactions(),
    ];

    return view('dashboards.inventory', $data);
  }

  /**
   * Sales Dashboard - Sales performance metrics
   */
  protected function salesDashboard()
  {
    $data = [
      'pageTitle' => __('Sales Dashboard'),
      'salesOverview' => $this->getSalesOverview(),
      'targetProgress' => $this->getTargetProgress(),
      'topProducts' => $this->getTopSellingProducts(),
      'customerVisits' => $this->getRecentCustomerVisits(),
    ];

    return view('dashboards.sales', $data);
  }

  /**
   * Team Leader Dashboard - Team management view
   */
  protected function teamLeaderDashboard()
  {
    $data = [
      'pageTitle' => __('Team Dashboard'),
      'teamAttendance' => $this->getTeamAttendance(),
      'teamLeaves' => $this->getTeamLeaves(),
      'teamTasks' => $this->getTeamTasks(),
      'teamPerformance' => $this->getTeamPerformanceMetrics(),
    ];

    return view('dashboards.team-leader', $data);
  }

  /**
   * Field Employee Dashboard - Field operations view
   */
  protected function fieldEmployeeDashboard()
  {
    $data = [
      'pageTitle' => __('Field Dashboard'),
      'todayVisits' => $this->getTodayVisits(),
      'pendingTasks' => $this->getFieldTasks(),
      'attendanceStatus' => $this->getTodayAttendance(),
      'targets' => $this->getPersonalTargets(),
    ];

    return view('dashboards.field-employee', $data);
  }

  /**
   * Employee Dashboard - Standard employee view
   */
  protected function employeeDashboard()
  {
    $data = [
      'pageTitle' => __('My Dashboard'),
      'attendanceOverview' => $this->getPersonalAttendance(),
      'leaveBalance' => $this->getLeaveBalance(),
      'myTasks' => $this->getMyTasks(),
      'announcements' => $this->getLatestAnnouncements(),
    ];

    return view('dashboards.employee', $data);
  }

  /**
   * Client Dashboard - Client portal view
   */
  protected function clientDashboard()
  {
    $data = [
      'pageTitle' => __('Client Portal'),
      'projects' => $this->getClientProjects(),
      'invoices' => $this->getClientInvoices(),
      'orders' => $this->getClientOrders(),
      'supportTickets' => $this->getClientTickets(),
    ];

    return view('dashboards.client', $data);
  }

  // Helper methods for fetching dashboard data
  // These would be implemented based on your specific requirements

  protected function getAdminStats()
  {
    return [
      'total_users' => \App\Models\User::count(),
      'total_employees' => \App\Models\User::whereHas('roles', function ($q) {
        $q->where('name', '!=', 'client');
      })->count(),
      'active_projects' => 0, // Implement based on your project model
      'pending_approvals' => 0, // Implement based on approval system
      'system_health' => 'Good',
    ];
  }

  protected function getEmployeeStats()
  {
    return [
      'total_employees' => \App\Models\User::whereHas('roles', function ($q) {
        $q->where('name', '!=', 'client');
      })->count(),
      'present_today' => 0, // Implement based on attendance
      'on_leave' => 0, // Implement based on leave requests
      'new_joiners' => \App\Models\User::where('created_at', '>=', now()->subDays(30))->count(),
    ];
  }

  protected function getAttendanceOverview()
  {
    // Implement attendance overview logic
    return [];
  }

  protected function getPendingLeaveRequests()
  {
    // Implement pending leave requests logic
    return [];
  }

  protected function getUpcomingHolidays()
  {
    // Implement upcoming holidays logic
    return [];
  }

  protected function getFinancialOverview()
  {
    // Implement financial overview logic
    return [];
  }

  protected function getPendingInvoices()
  {
    // Implement pending invoices logic
    return [];
  }

  protected function getExpenseOverview()
  {
    // Implement expense overview logic
    return [];
  }

  protected function getCashFlowData()
  {
    // Implement cash flow logic
    return [];
  }

  protected function getLeadStats()
  {
    // Implement lead statistics logic
    return [];
  }

  protected function getDealPipeline()
  {
    // Implement deal pipeline logic
    return [];
  }

  protected function getCRMActivities()
  {
    // Implement CRM activities logic
    return [];
  }

  protected function getTaskOverview()
  {
    // Implement task overview logic
    return [];
  }

  protected function getProjectStats()
  {
    // Implement project statistics logic
    return [];
  }

  protected function getTaskProgress()
  {
    // Implement task progress logic
    return [];
  }

  protected function getTeamPerformance()
  {
    // Implement team performance logic
    return [];
  }

  protected function getUpcomingDeadlines()
  {
    // Implement upcoming deadlines logic
    return [];
  }

  protected function getStockOverview()
  {
    // Implement stock overview logic
    return [];
  }

  protected function getLowStockAlerts()
  {
    // Implement low stock alerts logic
    return [];
  }

  protected function getWarehouseUtilization()
  {
    // Implement warehouse utilization logic
    return [];
  }

  protected function getRecentInventoryTransactions()
  {
    // Implement recent inventory transactions logic
    return [];
  }

  protected function getSalesOverview()
  {
    // Implement sales overview logic
    return [];
  }

  protected function getTargetProgress()
  {
    // Implement target progress logic
    return [];
  }

  protected function getTopSellingProducts()
  {
    // Implement top selling products logic
    return [];
  }

  protected function getRecentCustomerVisits()
  {
    // Implement recent customer visits logic
    return [];
  }

  protected function getTeamAttendance()
  {
    // Implement team attendance logic
    return [];
  }

  protected function getTeamLeaves()
  {
    // Implement team leaves logic
    return [];
  }

  protected function getTeamTasks()
  {
    // Implement team tasks logic
    return [];
  }

  protected function getTeamPerformanceMetrics()
  {
    // Implement team performance metrics logic
    return [];
  }

  protected function getTodayVisits()
  {
    // Implement today's visits logic
    return [];
  }

  protected function getFieldTasks()
  {
    // Implement field tasks logic
    return [];
  }

  protected function getTodayAttendance()
  {
    // Implement today's attendance logic
    return [];
  }

  protected function getPersonalTargets()
  {
    // Implement personal targets logic
    return [];
  }

  protected function getPersonalAttendance()
  {
    // Implement personal attendance logic
    $currentMonth = now()->startOfMonth();
    $workingDays = now()->diffInWeekdays($currentMonth);

    return [
      'working_days' => $workingDays,
      'present_days' => max(0, $workingDays - 2), // Mock data
      'absent_days' => 1, // Mock data
      'leave_days' => 1, // Mock data
    ];
  }

  protected function getLeaveBalance()
  {
    // Implement leave balance logic
    return [
      'total' => 21, // Mock data
      'used' => 5, // Mock data
      'available' => 16, // Mock data
      'pending' => 2, // Mock data
    ];
  }

  protected function getMyTasks()
  {
    // Implement my tasks logic
    return [
      [
        'id' => 1,
        'title' => 'Complete project documentation',
        'due_date' => now()->addDays(2)->format('M d, Y'),
        'priority' => 'high',
        'status' => 'in_progress',
      ],
      [
        'id' => 2,
        'title' => 'Review team performance reports',
        'due_date' => now()->addDays(5)->format('M d, Y'),
        'priority' => 'medium',
        'status' => 'pending',
      ],
      [
        'id' => 3,
        'title' => 'Update client presentation',
        'due_date' => now()->addDays(7)->format('M d, Y'),
        'priority' => 'low',
        'status' => 'pending',
      ],
    ];
  }

  protected function getLatestAnnouncements()
  {
    // Implement latest announcements logic
    return [
      [
        'id' => 1,
        'title' => 'Company Holiday Notice',
        'excerpt' => 'Office will be closed on national holiday next week.',
        'date' => now()->subDays(1)->format('M d, Y'),
      ],
      [
        'id' => 2,
        'title' => 'New HR Policy Update',
        'excerpt' => 'Updated work from home policy is now in effect.',
        'date' => now()->subDays(3)->format('M d, Y'),
      ],
    ];
  }

  protected function getRecentActivities()
  {
    // Implement recent activities logic
    return [];
  }

  protected function getSystemHealth()
  {
    // Implement system health logic
    return [];
  }

  protected function getClientProjects()
  {
    // Implement client projects logic
    return [];
  }

  protected function getClientInvoices()
  {
    // Implement client invoices logic
    return [];
  }

  protected function getClientOrders()
  {
    // Implement client orders logic
    return [];
  }

  protected function getClientTickets()
  {
    // Implement client tickets logic
    return [];
  }

  /**
   * Get list of enabled addon modules
   */
  protected function getEnabledAddons()
  {
    $addons = [
      // Core Modules
      'HRCore' => [
        'name' => 'HRCore',
        'label' => __('Human Resources'),
        'icon' => 'bx bx-group',
        'url' => route('hrcore.dashboard.index'),
      ],
      'CRMCore' => [
        'name' => 'CRMCore',
        'label' => __('CRM'),
        'icon' => 'bx bx-user-circle',
        'url' => route('crm.dashboard.index'),
      ],
      'AICore' => [
        'name' => 'AICore',
        'label' => __('AI Assistant'),
        'icon' => 'bx bx-bot',
        'url' => route('aicore.dashboard'),
      ],
      'WMSInventoryCore' => [
        'name' => 'WMSInventoryCore',
        'label' => __('Inventory Management'),
        'icon' => 'bx bx-package',
        'url' => route('wmsinventorycore.dashboard.index'),
      ],
      'AccountingCore' => [
        'name' => 'AccountingCore',
        'label' => __('Accounting'),
        'icon' => 'bx bx-calculator',
        'url' => '/accountingcore/dashboard',
      ],
      'AccountingPro' => [
        'name' => 'AccountingPro',
        'label' => __('Accounting Pro'),
        'icon' => 'bx bx-calculator',
        'url' => '/accountingpro/dashboard',
      ],
      'PMCore' => [
        'name' => 'PMCore',
        'label' => __('Project Management'),
        'icon' => 'bx bx-folder',
        'url' => route('pmcore.dashboard.index'),
      ],
      'SystemCore' => [
        'name' => 'SystemCore',
        'label' => __('System Management'),
        'icon' => 'bx bx-cog',
        'url' => route('settings.index'),
      ],
      // Addon Modules
      'FieldManager' => [
        'name' => 'FieldManager',
        'label' => __('Field Operations'),
        'icon' => 'bx bx-map',
        'url' => '/liveLocation',
      ],
    ];

    $enabledAddons = [];
    foreach ($addons as $key => $addon) {
      // Skip AccountingCore if AccountingPro is enabled
      if ($key === 'AccountingCore' && $this->addonService->isAddonEnabled('AccountingPro')) {
        continue;
      }

      if ($this->addonService->isAddonEnabled($key)) {
        $enabledAddons[$key] = $addon;
      }
    }

    return $enabledAddons;
  }

  /**
   * Get addon-specific widgets for dashboard
   */
  protected function getAddonWidgets($role)
  {
    $widgets = [];

    // HRCore widget
    if ($this->addonService->isAddonEnabled('HRCore')) {
      $widgets['hr'] = [
        'title' => __('HR Overview'),
        'data' => $this->getHRCoreWidget($role),
      ];
    }

    // CRMCore widget
    if ($this->addonService->isAddonEnabled('CRMCore')) {
      $widgets['crm'] = [
        'title' => __('CRM Overview'),
        'data' => $this->getCRMWidget($role),
      ];
    }

    // AICore widget
    if ($this->addonService->isAddonEnabled('AICore')) {
      $widgets['ai'] = [
        'title' => __('AI Assistant'),
        'data' => $this->getAICoreWidget($role),
      ];
    }

    // WMSInventoryCore widget
    if ($this->addonService->isAddonEnabled('WMSInventoryCore')) {
      $widgets['inventory'] = [
        'title' => __('Inventory Alerts'),
        'data' => $this->getInventoryWidget($role),
      ];
    }

    // PMCore widget
    if ($this->addonService->isAddonEnabled('PMCore')) {
      $widgets['projects'] = [
        'title' => __('Active Projects'),
        'data' => $this->getProjectWidget($role),
      ];
    }

    // Accounting widget - Check for AccountingPro first, then AccountingCore
    if ($this->addonService->isAddonEnabled('AccountingPro')) {
      $widgets['accounting'] = [
        'title' => __('Accounting Pro Overview'),
        'data' => $this->getAccountingProWidget($role),
      ];
    } elseif ($this->addonService->isAddonEnabled('AccountingCore')) {
      $widgets['accounting'] = [
        'title' => __('Financial Overview'),
        'data' => $this->getAccountingCoreWidget($role),
      ];
    }

    // SystemCore widget
    if ($this->addonService->isAddonEnabled('SystemCore')) {
      $widgets['system'] = [
        'title' => __('System Health'),
        'data' => $this->getSystemCoreWidget($role),
      ];
    }

    // ShiftPlus widget
    if ($this->addonService->isAddonEnabled('ShiftPlus')) {
      $widgets['shiftplus'] = [
        'title' => __('Today\'s Shifts'),
        'data' => $this->getShiftPlusWidget($role),
      ];
    }

    // SearchPlus widget
    if ($this->addonService->isAddonEnabled('SearchPlus')) {
      $widgets['searchplus'] = [
        'title' => __('Search Plus'),
        'data' => $this->getSearchPlusWidget($role),
      ];
    }

    return $widgets;
  }

  /**
   * Get ShiftPlus widget data
   */
  protected function getShiftPlusWidget($role)
  {
    try {
      if (!class_exists('\Modules\ShiftPlus\app\Models\RosterEntry')) {
        return null;
      }

      $today = now()->format('Y-m-d');
      $todayShifts = \Modules\ShiftPlus\app\Models\RosterEntry::whereDate('date', $today)
        ->whereHas('roster', function ($query) {
          $query->where('status', 'published');
        })
        ->count();

      return [
        'today_shifts' => $todayShifts,
        'url' => route('shiftplus.index'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get Inventory widget data
   */
  protected function getInventoryWidget($role)
  {
    try {
      if (!class_exists('\Modules\WMSInventoryCore\app\Models\Product')) {
        return null;
      }

      $lowStockCount = \Modules\WMSInventoryCore\app\Models\Product::whereRaw('current_stock <= reorder_level')
        ->where('track_stock', true)
        ->count();

      return [
        'low_stock_items' => $lowStockCount,
        'url' => route('wmsinventorycore.products.index'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get Project widget data
   */
  protected function getProjectWidget($role)
  {
    try {
      if (!class_exists('\Modules\PMCore\app\Models\Project')) {
        return null;
      }

      $activeProjects = \Modules\PMCore\app\Models\Project::where('status', 'active')->count();
      //TODO: Implement overdue tasks count
      /*  $overdueTasks = \Modules\PMCore\app\Models\Task::where('status', '!=', 'completed')
          ->whereDate('due_date', '<', now())
          ->count();*/

      $overdueTasks = 0; // Placeholder for overdue tasks count

      return [
        'active_projects' => $activeProjects,
        'overdue_tasks' => $overdueTasks,
        'url' => route('pmcore.projects.index'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get Accounting widget data
   */
  /**
   * Get AccountingCore widget data (Basic accounting)
   */
  protected function getAccountingCoreWidget($role)
  {
    try {
      if (!class_exists('\Modules\AccountingCore\app\Models\BasicTransaction')) {
        return null;
      }

      $currentMonth = now()->startOfMonth();
      $monthlyIncome = \Modules\AccountingCore\app\Models\BasicTransaction::where('type', 'income')
        ->where('transaction_date', '>=', $currentMonth)
        ->sum('amount');

      $monthlyExpense = \Modules\AccountingCore\app\Models\BasicTransaction::where('type', 'expense')
        ->where('transaction_date', '>=', $currentMonth)
        ->sum('amount');

      return [
        'monthly_income' => $monthlyIncome,
        'monthly_expense' => $monthlyExpense,
        'net_income' => $monthlyIncome - $monthlyExpense,
        'url' => '/accountingcore/dashboard',
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get AccountingPro widget data (Advanced accounting)
   */
  protected function getAccountingProWidget($role)
  {
    try {
      if (!class_exists('\Modules\AccountingPro\app\Models\JournalEntry')) {
        return null;
      }

      $pendingEntries = \Modules\AccountingPro\app\Models\JournalEntry::where('is_posted', false)
        ->where('is_reversed', false)
        ->count();

      return [
        'pending_entries' => $pendingEntries,
        'url' => '/accountingpro/dashboard',
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get SearchPlus widget data
   */
  protected function getSearchPlusWidget($role)
  {
    try {
      $controller = app(\Modules\SearchPlus\app\Http\Controllers\SearchPlusDashboardController::class);
      $data = $controller->getWidgetData();

      return [
        'total_indexed' => $data['total_indexed'],
        'recent_searches' => $data['recent_searches'],
        'last_indexed' => $data['last_indexed'],
        'url' => route('settings.index', ['module' => 'SearchPlus']),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get CRM widget data
   */
  protected function getCRMWidget($role)
  {
    try {
      if (!class_exists('\Modules\CRMCore\app\Models\Company')) {
        return null;
      }

      $totalCompanies = \Modules\CRMCore\app\Models\Company::count();
      $totalContacts = \Modules\CRMCore\app\Models\Contact::count();
      $totalDeals = \Modules\CRMCore\app\Models\Deal::count();
      $totalTasks = \Modules\CRMCore\app\Models\Task::count();

      // Get won deal stage ID
      $wonDealStage = \Modules\CRMCore\app\Models\DealStage::where('name', 'like', '%won%')
        ->orWhere('name', 'like', '%closed%')
        ->first();

      $totalRevenue = 0;
      if ($wonDealStage) {
        $totalRevenue = \Modules\CRMCore\app\Models\Deal::where('deal_stage_id', $wonDealStage->id)
          ->sum('value');
      }

      return [
        'total_companies' => $totalCompanies,
        'total_contacts' => $totalContacts,
        'total_deals' => $totalDeals,
        'total_tasks' => $totalTasks,
        'total_revenue' => $totalRevenue,
        'url' => route('crm.dashboard.index'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get HRCore widget data
   */
  protected function getHRCoreWidget($role)
  {
    try {
      // Count users with employee roles (exclude clients)
      $totalEmployees = \App\Models\User::whereHas('roles', function ($q) {
        $q->where('name', '!=', 'client');
      })->count();
      $totalDepartments = \Modules\HRCore\app\Models\Department::count();
      $totalDesignations = \Modules\HRCore\app\Models\Designation::count();
      $totalTeams = \Modules\HRCore\app\Models\Team::count();

      // Get today's attendance
      $todayPresent = 0;
      $todayAbsent = 0;
      if (class_exists('\Modules\HRCore\app\Models\Attendance')) {
        $todayPresent = \Modules\HRCore\app\Models\Attendance::whereDate('created_at', today())
          ->where('check_in_time', '!=', null)
          ->distinct('employee_id')
          ->count();
        $todayAbsent = $totalEmployees - $todayPresent;
      }

      // Get pending leave requests
      $pendingLeaves = 0;
      if (class_exists('\Modules\HRCore\app\Models\LeaveRequest')) {
        $pendingLeaves = \Modules\HRCore\app\Models\LeaveRequest::where('status', 'pending')->count();
      }

      return [
        'total_employees' => $totalEmployees,
        'total_departments' => $totalDepartments,
        'total_designations' => $totalDesignations,
        'total_teams' => $totalTeams,
        'today_present' => $todayPresent,
        'today_absent' => $todayAbsent,
        'pending_leaves' => $pendingLeaves,
        'url' => route('hrcore.dashboard.index'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get AICore widget data
   */
  protected function getAICoreWidget($role)
  {
    try {
      if (!class_exists('\Modules\AICore\app\Models\ChatSession')) {
        return null;
      }

      $userId = auth()->id();
      $totalSessions = \Modules\AICore\app\Models\ChatSession::where('user_id', $userId)->count();
      $totalMessages = \Modules\AICore\app\Models\ChatMessage::whereHas('session', function ($q) use ($userId) {
        $q->where('user_id', $userId);
      })->count();

      // Get today's AI usage
      $todayMessages = \Modules\AICore\app\Models\ChatMessage::whereHas('session', function ($q) use ($userId) {
        $q->where('user_id', $userId);
      })->whereDate('created_at', today())->count();

      // Get active AI providers
      $activeProviders = [];
      $providerSettings = \Modules\AICore\app\Models\AICoreSettings::first();
      if ($providerSettings) {
        if ($providerSettings->openai_enabled)
          $activeProviders[] = 'OpenAI';
        if ($providerSettings->claude_enabled)
          $activeProviders[] = 'Claude';
        if ($providerSettings->gemini_enabled)
          $activeProviders[] = 'Gemini';
        if ($providerSettings->azure_enabled)
          $activeProviders[] = 'Azure';
      }

      return [
        'total_sessions' => $totalSessions,
        'total_messages' => $totalMessages,
        'today_messages' => $todayMessages,
        'active_providers' => $activeProviders,
        'url' => route('aicore.dashboard'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }

  /**
   * Get SystemCore widget data
   */
  protected function getSystemCoreWidget($role)
  {
    try {
      // Get system statistics
      $totalModules = count(\Nwidart\Modules\Facades\Module::all());
      $enabledModules = count(\Nwidart\Modules\Facades\Module::allEnabled());

      // Get database size
      $dbSize = 0;
      try {
        $result = DB::select("SELECT SUM(data_length + index_length) / 1024 / 1024 AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
        $dbSize = round($result[0]->size_mb ?? 0, 2);
      } catch (\Exception $e) {
        // Ignore database size errors
      }

      // Get cache status
      $cacheDriver = config('cache.default');

      // Get queue status
      $queueDriver = config('queue.default');
      $failedJobs = 0;
      try {
        if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
          $failedJobs = DB::table('failed_jobs')->count();
        }
      } catch (\Exception $e) {
        // Table doesn't exist or other error, default to 0
        $failedJobs = 0;
      }

      return [
        'total_modules' => $totalModules,
        'enabled_modules' => $enabledModules,
        'db_size' => $dbSize,
        'cache_driver' => $cacheDriver,
        'queue_driver' => $queueDriver,
        'failed_jobs' => $failedJobs,
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
        'url' => route('settings.index'),
      ];
    } catch (\Exception $e) {
      return null;
    }
  }
}
