<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Services\AddonService\AddonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ImportExportController extends Controller
{
    protected AddonService $addonService;

    public function __construct(AddonService $addonService)
    {
        $this->addonService = $addonService;
    }

    /**
     * Show import/export interface for master data
     */
    public function index(Request $request): View
    {
        // Check if DataImportExport addon is available
        if (!$this->addonService->isAddonEnabled('DataImportExport')) {
            abort(404, 'Import/Export functionality is not available');
        }

        $masterDataTypes = $this->getMasterDataTypes();
        $selectedType = $request->get('type', 'all');
        $action = $request->get('action', 'import');

        return view('master-data.import-export.index', compact(
            'masterDataTypes',
            'selectedType', 
            'action'
        ));
    }

    /**
     * Get available master data types for import/export
     */
    protected function getMasterDataTypes(): array
    {
        $types = [
            'all' => [
                'label' => __('All Master Data'),
                'description' => __('Import/Export all master data types'),
                'icon' => 'bx bx-data',
                'tables' => []
            ]
        ];

        // Core master data types (always available)
        $coreTypes = [
            'lead_statuses' => [
                'label' => __('Lead Statuses'),
                'description' => __('CRM lead status options'),
                'icon' => 'bx bx-check-shield',
                'module' => 'core'
            ],
            'lead_sources' => [
                'label' => __('Lead Sources'),
                'description' => __('CRM lead source options'),
                'icon' => 'bx bx-link-alt',
                'module' => 'core'
            ],
            'deal_pipelines' => [
                'label' => __('Deal Pipelines'),
                'description' => __('Sales pipeline stages'),
                'icon' => 'bx bx-pie-chart-alt-2',
                'module' => 'core'
            ],
            'task_statuses' => [
                'label' => __('Task Statuses'),
                'description' => __('Task status options'),
                'icon' => 'bx bx-check-circle',
                'module' => 'core'
            ],
            'task_priorities' => [
                'label' => __('Task Priorities'),
                'description' => __('Task priority levels'),
                'icon' => 'bx bx-flag',
                'module' => 'core'
            ],
            'expense_types' => [
                'label' => __('Expense Types'),
                'description' => __('Expense category options'),
                'icon' => 'bx bx-category',
                'module' => 'core'
            ]
        ];

        $types = array_merge($types, $coreTypes);

        // Module-specific master data types (conditional)
        if ($this->addonService->isModuleAvailable('HRCore')) {
            $types['shifts'] = [
                'label' => __('Shifts'),
                'description' => __('Work shift schedules'),
                'icon' => 'bx bx-time-five',
                'module' => 'HRCore'
            ];
            $types['holidays'] = [
                'label' => __('Holidays'),
                'description' => __('Company holidays'),
                'icon' => 'bx bx-calendar-x',
                'module' => 'HRCore'
            ];
        }

        if ($this->addonService->isModuleAvailable('WMSInventoryCore')) {
            $types['categories'] = [
                'label' => __('Product Categories'),
                'description' => __('Inventory product categories'),
                'icon' => 'bx bx-category',
                'module' => 'WMSInventoryCore'
            ];
            $types['units'] = [
                'label' => __('Units of Measure'),
                'description' => __('Measurement units'),
                'icon' => 'bx bx-ruler',
                'module' => 'WMSInventoryCore'
            ];
            $types['adjustment_types'] = [
                'label' => __('Adjustment Types'),
                'description' => __('Inventory adjustment types'),
                'icon' => 'bx bx-list-check',
                'module' => 'WMSInventoryCore'
            ];
        }

        if ($this->addonService->isModuleAvailable('AccountingCore')) {
            $types['chart_of_accounts'] = [
                'label' => __('Chart of Accounts'),
                'description' => __('Accounting chart of accounts'),
                'icon' => 'bx bx-list-ul',
                'module' => 'AccountingCore'
            ];
            $types['tax_rates'] = [
                'label' => __('Tax Rates'),
                'description' => __('Tax rate configurations'),
                'icon' => 'bx bx-percent',
                'module' => 'AccountingCore'
            ];
        }

        if ($this->addonService->isModuleAvailable('DocumentManagement')) {
            $types['document_types'] = [
                'label' => __('Document Types'),
                'description' => __('Document type categories'),
                'icon' => 'bx bx-file',
                'module' => 'DocumentManagement'
            ];
        }

        if ($this->addonService->isModuleAvailable('DisciplinaryActions')) {
            $types['warning_types'] = [
                'label' => __('Warning Types'),
                'description' => __('Warning type categories'),
                'icon' => 'bx bx-list-ul',
                'module' => 'DisciplinaryActions'
            ];
        }

        // Attendance system addons
        if ($this->addonService->isModuleAvailable('IpAddressAttendance')) {
            $types['ip_groups'] = [
                'label' => __('IP Groups'),
                'description' => __('IP address groups for attendance'),
                'icon' => 'bx bx-network-chart',
                'module' => 'IpAddressAttendance'
            ];
        }

        if ($this->addonService->isModuleAvailable('GeofenceSystem')) {
            $types['geofence_groups'] = [
                'label' => __('Geofence Groups'),
                'description' => __('Geofence location groups'),
                'icon' => 'bx bx-map',
                'module' => 'GeofenceSystem'
            ];
        }

        if ($this->addonService->isModuleAvailable('QRAttendance')) {
            $types['qr_codes'] = [
                'label' => __('QR Code Groups'),
                'description' => __('QR codes for attendance'),
                'icon' => 'bx bx-qr',
                'module' => 'QRAttendance'
            ];
        }

        if ($this->addonService->isModuleAvailable('SiteAttendance')) {
            $types['sites'] = [
                'label' => __('Sites'),
                'description' => __('Site locations'),
                'icon' => 'bx bx-buildings',
                'module' => 'SiteAttendance'
            ];
        }

        if ($this->addonService->isModuleAvailable('MultiCurrency')) {
            $types['currencies'] = [
                'label' => __('Currencies'),
                'description' => __('Currency configurations'),
                'icon' => 'bx bx-dollar-circle',
                'module' => 'MultiCurrency'
            ];
        }

        return $types;
    }

    /**
     * Get import template for specific master data type
     */
    public function getTemplate(Request $request): JsonResponse
    {
        if (!$this->addonService->isAddonEnabled('DataImportExport')) {
            return response()->json(['error' => 'Import/Export functionality is not available'], 404);
        }

        $type = $request->get('type');
        $masterDataTypes = $this->getMasterDataTypes();

        if (!isset($masterDataTypes[$type])) {
            return response()->json(['error' => 'Invalid master data type'], 400);
        }

        // Delegate to DataImportExport addon for actual template generation
        if (class_exists('\Modules\DataImportExport\Http\Controllers\TemplateController')) {
            $templateController = app('\Modules\DataImportExport\Http\Controllers\TemplateController');
            return $templateController->getMasterDataTemplate($type);
        }

        return response()->json(['error' => 'Template service not available'], 503);
    }

    /**
     * Process import for master data
     */
    public function import(Request $request): JsonResponse
    {
        if (!$this->addonService->isAddonEnabled('DataImportExport')) {
            return response()->json(['error' => 'Import/Export functionality is not available'], 404);
        }

        $request->validate([
            'type' => 'required|string',
            'file' => 'required|file|mimes:csv,xlsx,xls',
            'options' => 'nullable|array'
        ]);

        // Delegate to DataImportExport addon for actual import processing
        if (class_exists('\Modules\DataImportExport\Http\Controllers\ImportController')) {
            $importController = app('\Modules\DataImportExport\Http\Controllers\ImportController');
            return $importController->processMasterDataImport($request);
        }

        return response()->json(['error' => 'Import service not available'], 503);
    }

    /**
     * Process export for master data
     */
    public function export(Request $request): JsonResponse
    {
        if (!$this->addonService->isAddonEnabled('DataImportExport')) {
            return response()->json(['error' => 'Import/Export functionality is not available'], 404);
        }

        $request->validate([
            'type' => 'required|string',
            'format' => 'required|in:csv,xlsx,pdf',
            'filters' => 'nullable|array'
        ]);

        // Delegate to DataImportExport addon for actual export processing
        if (class_exists('\Modules\DataImportExport\Http\Controllers\ExportController')) {
            $exportController = app('\Modules\DataImportExport\Http\Controllers\ExportController');
            return $exportController->processMasterDataExport($request);
        }

        return response()->json(['error' => 'Export service not available'], 503);
    }

    /**
     * Get import/export status
     */
    public function status(Request $request): JsonResponse
    {
        if (!$this->addonService->isAddonEnabled('DataImportExport')) {
            return response()->json(['error' => 'Import/Export functionality is not available'], 404);
        }

        $jobId = $request->get('job_id');

        // Delegate to DataImportExport addon for status checking
        if (class_exists('\Modules\DataImportExport\Http\Controllers\StatusController')) {
            $statusController = app('\Modules\DataImportExport\Http\Controllers\StatusController');
            return $statusController->getJobStatus($jobId);
        }

        return response()->json(['error' => 'Status service not available'], 503);
    }
}