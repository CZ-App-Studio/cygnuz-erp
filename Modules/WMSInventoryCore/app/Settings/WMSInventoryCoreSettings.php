<?php

namespace Modules\WMSInventoryCore\app\Settings;

use App\Services\Settings\BaseModuleSettings;
use Illuminate\Support\Facades\DB;

class WMSInventoryCoreSettings extends BaseModuleSettings
{
    protected string $module = 'WMSInventoryCore';

    /**
     * Get module display name
     */
    public function getModuleName(): string
    {
        return __('Inventory Management Settings');
    }

    /**
     * Get module description
     */
    public function getModuleDescription(): string
    {
        return __('Configure inventory tracking, stock management, and warehouse operations');
    }

    /**
     * Get module icon
     */
    public function getModuleIcon(): string
    {
        return 'bx bx-package';
    }

    /**
     * Define module settings
     */
    protected function define(): array
    {
        return [
            'product_management' => [
                'auto_generate_sku' => [
                    'type' => 'toggle',
                    'label' => __('Auto Generate SKU'),
                    'help' => __('Automatically generate SKU codes for new products'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'sku_prefix' => [
                    'type' => 'text',
                    'label' => __('SKU Prefix'),
                    'help' => __('Prefix for auto-generated SKU codes'),
                    'default' => 'PRD',
                    'validation' => 'nullable|string|max:10',
                ],
                'require_product_images' => [
                    'type' => 'toggle',
                    'label' => __('Require Product Images'),
                    'help' => __('Make product images mandatory when creating products'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'allow_negative_stock' => [
                    'type' => 'toggle',
                    'label' => __('Allow Negative Stock'),
                    'help' => __('Allow stock levels to go below zero'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
            ],

            'stock_tracking' => [
                'default_stock_method' => [
                    'type' => 'select',
                    'label' => __('Default Stock Valuation Method'),
                    'help' => __('Default method for stock valuation'),
                    'default' => 'fifo',
                    'options' => [
                        'fifo' => __('FIFO (First In, First Out)'),
                        'lifo' => __('LIFO (Last In, First Out)'),
                        'average' => __('Weighted Average'),
                        'standard' => __('Standard Cost'),
                    ],
                    'validation' => 'required|string',
                ],
                'enable_batch_tracking' => [
                    'type' => 'toggle',
                    'label' => __('Enable Batch Tracking'),
                    'help' => __('Track products by batch numbers'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'enable_serial_tracking' => [
                    'type' => 'toggle',
                    'label' => __('Enable Serial Number Tracking'),
                    'help' => __('Track products by serial numbers'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'enable_expiry_tracking' => [
                    'type' => 'toggle',
                    'label' => __('Enable Expiry Date Tracking'),
                    'help' => __('Track product expiry dates'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
            ],

            'warehouse_operations' => [
                'default_warehouse_id' => [
                    'type' => 'select',
                    'label' => __('Default Warehouse'),
                    'help' => __('Default warehouse for new transactions'),
                    'default' => null,
                    'options' => [], // Will be populated dynamically
                    'validation' => 'nullable|exists:warehouses,id',
                ],
                'enable_multi_location' => [
                    'type' => 'toggle',
                    'label' => __('Enable Multi-Location Tracking'),
                    'help' => __('Track inventory across multiple warehouse locations'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'require_location_for_stock' => [
                    'type' => 'toggle',
                    'label' => __('Require Location for Stock Operations'),
                    'help' => __('Make location selection mandatory for stock movements'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'auto_create_bins' => [
                    'type' => 'toggle',
                    'label' => __('Auto Create Bin Locations'),
                    'help' => __('Automatically create default bin locations for new warehouses'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
            ],

            'inventory_alerts' => [
                'enable_low_stock_alerts' => [
                    'type' => 'toggle',
                    'label' => __('Enable Low Stock Alerts'),
                    'help' => __('Send alerts when stock falls below minimum level'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'enable_expiry_alerts' => [
                    'type' => 'toggle',
                    'label' => __('Enable Expiry Alerts'),
                    'help' => __('Send alerts for products nearing expiry'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'expiry_alert_days' => [
                    'type' => 'number',
                    'label' => __('Expiry Alert Days'),
                    'help' => __('Days before expiry to send alerts'),
                    'default' => '30',
                    'validation' => 'required|numeric|min:1|max:365',
                ],
            ],

            'adjustments' => [
                'require_approval_for_adjustments' => [
                    'type' => 'toggle',
                    'label' => __('Require Approval for Adjustments'),
                    'help' => __('All inventory adjustments must be approved'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
                'require_reason_for_adjustments' => [
                    'type' => 'toggle',
                    'label' => __('Require Reason for Adjustments'),
                    'help' => __('Make reason mandatory for inventory adjustments'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
            ],

            'transfers' => [
                'require_approval_for_transfers' => [
                    'type' => 'toggle',
                    'label' => __('Require Approval for Transfers'),
                    'help' => __('All inventory transfers must be approved'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'auto_receive_transfers' => [
                    'type' => 'toggle',
                    'label' => __('Auto Receive Transfers'),
                    'help' => __('Automatically receive transfers upon sending'),
                    'default' => false,
                    'validation' => 'boolean',
                ],
                'enable_in_transit_tracking' => [
                    'type' => 'toggle',
                    'label' => __('Enable In-Transit Tracking'),
                    'help' => __('Track inventory while in transit between warehouses'),
                    'default' => true,
                    'validation' => 'boolean',
                ],
            ],

        ];
    }

    /**
     * Get the settings definition for the module with dynamic options
     */
    public function getSettingsDefinition(): array
    {
        $settings = parent::getSettingsDefinition();

        // Dynamically populate warehouse options for default_warehouse_id
        if (isset($settings['warehouse_operations']['default_warehouse_id'])) {
            $warehouses = DB::table('warehouses')
                ->select('id', 'name', 'code')
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();

            $settings['warehouse_operations']['default_warehouse_id']['options'] = $warehouses;
        }

        return $settings;
    }
}
