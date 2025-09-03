<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AddonService\AddonService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Nwidart\Modules\Facades\Module;

class MasterDataController extends Controller
{
    protected AddonService $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Display master data dashboard
     */
    public function index(): View
    {
        $masterDataSections = $this->getMasterDataSections();
        $hasImportExport = $this->addonService->isAddonEnabled('DataImportExport');

        // Add import/export URLs to sections if available
        if ($hasImportExport) {
            $masterDataSections = $this->addImportExportUrls($masterDataSections);
        }

        return view('master-data.dashboard', compact('masterDataSections', 'hasImportExport'));
    }

    /**
     * Add import/export URLs to master data sections
     */
    protected function addImportExportUrls(array $sections): array
    {
        foreach ($sections as $sectionKey => &$section) {
            foreach ($section['items'] as &$item) {
                // Extract data type from URL or use a mapping
                $dataType = $this->extractDataTypeFromUrl($item['url']);
                if ($dataType) {
                    $item['importExport'] = [
                        'import' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'import']),
                        'export' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export']),
                        'template' => route('master-data.import-export.template', ['type' => $dataType]),
                    ];
                }
            }
        }

        return $sections;
    }

    /**
     * Extract data type from URL for import/export mapping
     */
    protected function extractDataTypeFromUrl(string $url): ?string
    {
        $mapping = [
            'leadStatuses.index' => 'lead_statuses',
            'leadSources.index' => 'lead_sources',
            'dealPipelines.index' => 'deal_pipelines',
            'taskStatuses.index' => 'task_statuses',
            'taskPriorities.index' => 'task_priorities',
            'pmcore.project-statuses.index' => 'project_statuses',
            'hrcore.shifts.index' => 'shifts',
            'hrcore.holidays.index' => 'holidays',
            'hrcore.leave-types.index' => 'leave_types',
            'hrcore.expense-types.index' => 'expense_types',
            'wmsinventorycore.categories.index' => 'categories',
            'wmsinventorycore.units.index' => 'units',
            'wmsinventorycore.adjustmenttypes.index' => 'adjustment_types',
            'accountingcore.categories.index' => 'basic_transaction_categories',
            'accountingcore.tax-rates.index' => 'tax_rates',
            'accountingpro.settings.chart-of-accounts.index' => 'chart_of_accounts',
            'accountingpro.settings.fiscal-periods.index' => 'fiscal_periods',
            'disciplinaryactions.warning-types.index' => 'warning_types',
            'documenttypes.index' => 'document_types',
            'ipgroup.index' => 'ip_groups',
            'geofencegroup.index' => 'geofence_groups',
            'qrcode.index' => 'qr_codes',
            'siteattendance.index' => 'sites',
            'currencies.index' => 'currencies',
        ];

        foreach ($mapping as $routePattern => $dataType) {
            if (strpos($url, $routePattern) !== false) {
                return $dataType;
            }
        }

        return null;
    }

    /**
     * Check if a module is available (either core module or enabled addon)
     */
    protected function isModuleAvailable(string $moduleName): bool
    {
        return $this->addonService->isModuleAvailable($moduleName);
    }

    /**
     * Get master data sections with their items
     */
    protected function getMasterDataSections(): array
    {
        $sections = [];

        // Core CRM Master Data (always available as it's core functionality)
        $sections['crm'] = [
            'title' => __('CRM Master Data'),
            'icon' => 'bx bx-briefcase',
            'items' => [
                [
                    'name' => __('Lead Statuses'),
                    'description' => __('Manage lead status options'),
                    'url' => route('settings.leadStatuses.index'),
                    'icon' => 'bx bx-check-shield',
                    'count' => $this->getItemCount('lead_statuses'),
                ],
                [
                    'name' => __('Lead Sources'),
                    'description' => __('Manage lead source options'),
                    'url' => route('settings.leadSources.index'),
                    'icon' => 'bx bx-link-alt',
                    'count' => $this->getItemCount('lead_sources'),
                ],
                [
                    'name' => __('Deal Pipelines'),
                    'description' => __('Manage sales pipeline stages'),
                    'url' => route('settings.dealPipelines.index'),
                    'icon' => 'bx bx-pie-chart-alt-2',
                    'count' => $this->getItemCount('deal_pipelines'),
                ],
            ],
        ];

        // Core Task Management (always available)
        $sections['tasks'] = [
            'title' => __('Task Management'),
            'icon' => 'bx bx-task',
            'items' => [
                [
                    'name' => __('Task Statuses'),
                    'description' => __('Manage task status options'),
                    'url' => route('settings.taskStatuses.index'),
                    'icon' => 'bx bx-check-circle',
                    'count' => $this->getItemCount('task_statuses'),
                ],
                [
                    'name' => __('Task Priorities'),
                    'description' => __('Manage task priority levels'),
                    'url' => route('settings.taskPriorities.index'),
                    'icon' => 'bx bx-flag',
                    'count' => $this->getItemCount('task_priorities'),
                ],
            ],
        ];

        // Project Management Core (PMCore is a core module)
        if ($this->isModuleAvailable('PMCore')) {
            $sections['projects'] = [
                'title' => __('Project Management'),
                'icon' => 'bx bx-briefcase',
                'items' => [
                    [
                        'name' => __('Project Statuses'),
                        'description' => __('Manage project status options'),
                        'url' => route('pmcore.project-statuses.index'),
                        'icon' => 'bx bx-check-shield',
                        'count' => $this->getItemCount('project_statuses'),
                    ],
                ],
            ];
        }

        // HR Master Data (HRCore is a core module)
        if ($this->isModuleAvailable('HRCore')) {
            $sections['hr'] = [
                'title' => __('Human Resources'),
                'icon' => 'bx bx-group',
                'items' => [
                    [
                        'name' => __('Shifts'),
                        'description' => __('Manage work shift schedules'),
                        'url' => route('hrcore.shifts.index'),
                        'icon' => 'bx bx-time-five',
                        'count' => $this->getItemCount('shifts'),
                    ],
                    [
                        'name' => __('Holidays'),
                        'description' => __('Manage company holidays'),
                        'url' => route('hrcore.holidays.index'),
                        'icon' => 'bx bx-calendar-x',
                        'count' => $this->getItemCount('holidays'),
                    ],
                    [
                        'name' => __('Leave Types'),
                        'description' => __('Manage leave types and policies'),
                        'url' => route('hrcore.leave-types.index'),
                        'icon' => 'bx bx-calendar-check',
                        'count' => $this->getItemCount('leave_types'),
                    ],
                ],
            ];
        }

        // Core Expense Management (part of HRCore)
        if ($this->isModuleAvailable('HRCore')) {
            $expenseItems = [
                [
                    'name' => __('Expense Types'),
                    'description' => __('Manage expense categories'),
                    'url' => route('hrcore.expense-types.index'),
                    'icon' => 'bx bx-category',
                    'count' => $this->getItemCount('expense_types'),
                ],
            ];

            // Add expense items to HR section if it exists, otherwise create new section
            if (isset($sections['hr'])) {
                $sections['hr']['items'] = array_merge($sections['hr']['items'], $expenseItems);
            } else {
                $sections['expense'] = [
                    'title' => __('Expense Management'),
                    'icon' => 'bx bx-receipt',
                    'items' => $expenseItems,
                ];
            }
        }

        // Add addon-specific sections
        $addonSections = $this->getAddonMasterDataSections();
        $sections = array_merge($sections, $addonSections);

        return $sections;
    }

    /**
     * Get count of items for a table
     */
    protected function getItemCount(string $table): int
    {
        try {
            return DB::table($table)->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get addon-specific master data sections
     */
    protected function getAddonMasterDataSections(): array
    {
        $sections = [];

        // Document Management addon
        if ($this->isModuleAvailable('DocumentManagement')) {
            $sections['documents'] = [
                'title' => __('Document Management'),
                'icon' => 'bx bx-folder',
                'items' => [
                    [
                        'name' => __('Document Types'),
                        'description' => __('Manage document type categories'),
                        'url' => route('documenttypes.index'),
                        'icon' => 'bx bx-file',
                        'count' => $this->getItemCount('document_types'),
                    ],
                ],
            ];
        }

        // WMS Inventory Core master data
        if ($this->isModuleAvailable('WMSInventoryCore')) {
            $sections['inventory'] = [
                'title' => __('Inventory Management'),
                'icon' => 'bx bx-package',
                'items' => [
                    [
                        'name' => __('Product Categories'),
                        'description' => __('Manage product categories'),
                        'url' => route('wmsinventorycore.categories.index'),
                        'icon' => 'bx bx-category',
                        'count' => $this->getItemCount('categories'),
                    ],
                    [
                        'name' => __('Units of Measure'),
                        'description' => __('Manage measurement units'),
                        'url' => route('wmsinventorycore.units.index'),
                        'icon' => 'bx bx-ruler',
                        'count' => $this->getItemCount('units'),
                    ],
                    [
                        'name' => __('Adjustment Types'),
                        'description' => __('Manage inventory adjustment types'),
                        'url' => route('wmsinventorycore.adjustmenttypes.index'),
                        'icon' => 'bx bx-list-check',
                        'count' => $this->getItemCount('adjustment_types'),
                    ],
                ],
            ];
        }

        // Accounting master data
        if ($this->isModuleAvailable('AccountingCore') || $this->isModuleAvailable('AccountingPro')) {
            $accountingItems = [];

            // Basic items from AccountingCore (available to both)
            if ($this->isModuleAvailable('AccountingCore')) {
                $accountingItems[] = [
                    'name' => __('Transaction Categories'),
                    'description' => __('Manage income and expense categories'),
                    'url' => route('accountingcore.categories.index'),
                    'icon' => 'bx bx-category',
                    'count' => $this->getItemCount('basic_transaction_categories'),
                ];

                $accountingItems[] = [
                    'name' => __('Tax Rates'),
                    'description' => __('Manage tax rate configurations'),
                    'url' => route('accountingcore.tax-rates.index'),
                    'icon' => 'bx bx-percent',
                    'count' => $this->getItemCount('tax_rates'),
                ];
            }

            // Advanced items from AccountingPro only
            if ($this->isModuleAvailable('AccountingPro')) {
                $accountingItems[] = [
                    'name' => __('Chart of Accounts'),
                    'description' => __('Manage chart of accounts'),
                    'url' => route('accountingpro.settings.chart-of-accounts.index'),
                    'icon' => 'bx bx-list-ul',
                    'count' => $this->getItemCount('chart_of_accounts'),
                ];

                $accountingItems[] = [
                    'name' => __('Fiscal Periods'),
                    'description' => __('Manage fiscal year periods'),
                    'url' => route('accountingpro.settings.fiscal-periods.index'),
                    'icon' => 'bx bx-calendar',
                    'count' => $this->getItemCount('fiscal_periods'),
                ];
            }

            $sections['accounting'] = [
                'title' => $this->isModuleAvailable('AccountingPro') ? __('Accounting Pro') : __('Accounting'),
                'icon' => 'bx bx-calculator',
                'items' => $accountingItems,
            ];
        }

        // Disciplinary Actions addon
        if ($this->isModuleAvailable('DisciplinaryActions')) {
            $sections['disciplinary'] = [
                'title' => __('Disciplinary Actions'),
                'icon' => 'bx bx-shield-x',
                'items' => [
                    [
                        'name' => __('Warning Types'),
                        'description' => __('Manage warning type categories'),
                        'url' => route('disciplinaryactions.warning-types.index'),
                        'icon' => 'bx bx-list-ul',
                        'count' => $this->getItemCount('warning_types'),
                    ],
                ],
            ];
        }

        // Attendance system addons
        $attendanceItems = [];

        if ($this->isModuleAvailable('IpAddressAttendance')) {
            $attendanceItems[] = [
                'name' => __('IP Groups'),
                'description' => __('Manage IP address groups for attendance'),
                'url' => route('ipgroup.index'),
                'icon' => 'bx bx-network-chart',
                'count' => $this->getItemCount('ip_groups'),
            ];
        }

        if ($this->isModuleAvailable('GeofenceSystem')) {
            $attendanceItems[] = [
                'name' => __('Geofence Groups'),
                'description' => __('Manage geofence locations'),
                'url' => route('geofencegroup.index'),
                'icon' => 'bx bx-map',
                'count' => $this->getItemCount('geofence_groups'),
            ];
        }

        if ($this->isModuleAvailable('QRAttendance')) {
            $attendanceItems[] = [
                'name' => __('QR Code Groups'),
                'description' => __('Manage QR codes for attendance'),
                'url' => route('qrcode.index'),
                'icon' => 'bx bx-qr',
                'count' => $this->getItemCount('qr_codes'),
            ];
        }

        if ($this->isModuleAvailable('SiteAttendance')) {
            $attendanceItems[] = [
                'name' => __('Sites'),
                'description' => __('Manage site locations'),
                'url' => route('siteattendance.index'),
                'icon' => 'bx bx-buildings',
                'count' => $this->getItemCount('sites'),
            ];
        }

        // Add attendance section if any items exist
        if (! empty($attendanceItems)) {
            $sections['attendance'] = [
                'title' => __('Attendance Systems'),
                'icon' => 'bx bx-time-five',
                'items' => $attendanceItems,
            ];
        }

        // Multi-Currency addon
        if ($this->isModuleAvailable('MultiCurrency')) {
            $sections['currency'] = [
                'title' => __('Multi-Currency'),
                'icon' => 'bx bx-dollar-circle',
                'items' => [
                    [
                        'name' => __('Currencies'),
                        'description' => __('Manage currency configurations'),
                        'url' => route('currencies.index'),
                        'icon' => 'bx bx-dollar-circle',
                        'count' => $this->getItemCount('currencies'),
                    ],
                ],
            ];
        }

        return $sections;
    }
}
