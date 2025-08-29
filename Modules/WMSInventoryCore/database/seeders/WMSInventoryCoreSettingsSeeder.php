<?php

namespace Modules\WMSInventoryCore\database\seeders;

use App\Models\ModuleSetting;
use Illuminate\Database\Seeder;

class WMSInventoryCoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Product Management
            'auto_generate_sku' => true,
            'sku_prefix' => 'PRD',
            'require_product_images' => false,
            'allow_negative_stock' => false,

            // Stock Tracking
            'default_stock_method' => 'fifo',
            'enable_batch_tracking' => false,
            'enable_serial_tracking' => false,
            'enable_expiry_tracking' => false,

            // Warehouse Operations
            'default_warehouse_id' => null,
            'enable_multi_location' => false,
            'require_location_for_stock' => false,
            'auto_create_bins' => true,

            // Inventory Alerts
            'enable_low_stock_alerts' => true,
            'enable_expiry_alerts' => true,
            'expiry_alert_days' => '30',

            // Adjustments
            'require_approval_for_adjustments' => true,
            'require_reason_for_adjustments' => true,

            // Transfers
            'require_approval_for_transfers' => false,
            'auto_receive_transfers' => false,
            'enable_in_transit_tracking' => true,
        ];

        foreach ($settings as $key => $value) {
            ModuleSetting::firstOrCreate(
                [
                    'module' => 'WMSInventoryCore',
                    'key' => $key,
                ],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
