<?php

namespace App\Traits;

use App\Services\AddonService\AddonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait HasImportExport
{
    /**
     * Check if import/export is available
     */
    protected function hasImportExport(): bool
    {
        $addonService = app(AddonService::class);
        return $addonService->isAddonEnabled('DataImportExport');
    }

    /**
     * Get import/export URLs for the view
     */
    protected function getImportExportUrls(string $dataType): array
    {
        if (!$this->hasImportExport()) {
            return [];
        }

        return [
            'import' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'import']),
            'export' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export']),
            'template' => route('master-data.import-export.template', ['type' => $dataType]),
        ];
    }

    /**
     * Add import/export buttons to page actions
     */
    protected function getImportExportActions(string $dataType): array
    {
        if (!$this->hasImportExport()) {
            return [];
        }

        return [
            [
                'type' => 'dropdown',
                'label' => __('Import/Export'),
                'icon' => 'bx bx-transfer-alt',
                'variant' => 'outline-secondary',
                'items' => [
                    [
                        'label' => __('Import Data'),
                        'icon' => 'bx bx-download',
                        'url' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'import'])
                    ],
                    [
                        'label' => __('Export Data'),
                        'icon' => 'bx bx-upload',
                        'url' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export'])
                    ],
                    [
                        'label' => __('Download Template'),
                        'icon' => 'bx bx-file-blank',
                        'url' => route('master-data.import-export.template', ['type' => $dataType]),
                        'target' => '_blank'
                    ]
                ]
            ]
        ];
    }

    /**
     * Quick export method for DataTables export buttons
     */
    public function quickExport(Request $request, string $dataType): JsonResponse
    {
        if (!$this->hasImportExport()) {
            return response()->json(['error' => 'Import/Export functionality is not available'], 404);
        }

        $format = $request->get('format', 'xlsx');
        $search = $request->get('search', '');
        $filters = $request->get('filters', []);

        // Delegate to DataImportExport addon
        if (class_exists('\Modules\DataImportExport\Http\Controllers\ExportController')) {
            $exportController = app('\Modules\DataImportExport\Http\Controllers\ExportController');
            
            return $exportController->processMasterDataExport(
                $request->merge([
                    'type' => $dataType,
                    'format' => $format,
                    'quick_export' => true,
                    'search' => $search,
                    'filters' => $filters
                ])
            );
        }

        return response()->json(['error' => 'Export service not available'], 503);
    }

    /**
     * Get export button configuration for DataTables
     */
    protected function getDataTableExportButtons(string $dataType): array
    {
        if (!$this->hasImportExport()) {
            return [];
        }

        return [
            [
                'extend' => 'collection',
                'text' => '<i class="bx bx-download me-1"></i>' . __('Export'),
                'className' => 'btn btn-outline-secondary dropdown-toggle',
                'buttons' => [
                    [
                        'text' => '<i class="bx bx-file-blank me-2"></i>' . __('Excel'),
                        'action' => "function(e, dt, button, config) { 
                            window.location.href = '" . route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export']) . "&format=xlsx';
                        }"
                    ],
                    [
                        'text' => '<i class="bx bx-file me-2"></i>' . __('CSV'),
                        'action' => "function(e, dt, button, config) { 
                            window.location.href = '" . route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export']) . "&format=csv';
                        }"
                    ],
                    [
                        'text' => '<i class="bx bx-file-pdf me-2"></i>' . __('PDF'),
                        'action' => "function(e, dt, button, config) { 
                            window.location.href = '" . route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export']) . "&format=pdf';
                        }"
                    ]
                ]
            ],
            [
                'text' => '<i class="bx bx-upload me-1"></i>' . __('Import'),
                'className' => 'btn btn-outline-primary',
                'action' => "function(e, dt, button, config) { 
                    window.location.href = '" . route('master-data.import-export.index', ['type' => $dataType, 'action' => 'import']) . "';
                }"
            ]
        ];
    }

    /**
     * Add import/export meta tags for SEO and sharing
     */
    protected function getImportExportMeta(string $dataType): array
    {
        if (!$this->hasImportExport()) {
            return [];
        }

        return [
            'data-import-url' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'import']),
            'data-export-url' => route('master-data.import-export.index', ['type' => $dataType, 'action' => 'export']),
            'data-template-url' => route('master-data.import-export.template', ['type' => $dataType]),
            'data-has-import-export' => 'true'
        ];
    }

    /**
     * Get import/export permission checks
     */
    protected function getImportExportPermissions(): array
    {
        return [
            'can_import' => $this->hasImportExport() && $this->canImport(),
            'can_export' => $this->hasImportExport() && $this->canExport(),
            'can_download_template' => $this->hasImportExport() && $this->canDownloadTemplate(),
        ];
    }

    /**
     * Permission methods (override in implementing classes)
     */
    protected function canImport(): bool
    {
        return true; // Override in implementing class
    }

    protected function canExport(): bool
    {
        return true; // Override in implementing class
    }

    protected function canDownloadTemplate(): bool
    {
        return true; // Override in implementing class
    }

    /**
     * Get JavaScript configuration for import/export
     */
    protected function getImportExportJsConfig(string $dataType): array
    {
        if (!$this->hasImportExport()) {
            return [];
        }

        return [
            'hasImportExport' => true,
            'dataType' => $dataType,
            'urls' => [
                'import' => route('master-data.import-export.import'),
                'export' => route('master-data.import-export.export'),
                'template' => route('master-data.import-export.template'),
                'status' => route('master-data.import-export.status'),
                'quickExport' => route('master-data.quick-export', ['type' => $dataType])
            ],
            'permissions' => $this->getImportExportPermissions(),
            'labels' => [
                'importData' => __('Import Data'),
                'exportData' => __('Export Data'),
                'downloadTemplate' => __('Download Template'),
                'selectFile' => __('Select File'),
                'uploadFile' => __('Upload File'),
                'processing' => __('Processing...'),
                'importSuccess' => __('Data imported successfully'),
                'exportSuccess' => __('Data exported successfully'),
                'importError' => __('Import failed'),
                'exportError' => __('Export failed'),
            ]
        ];
    }
}