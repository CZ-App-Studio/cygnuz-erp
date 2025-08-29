<?php

namespace Modules\WMSInventoryCore\app\Services;

class WMSInventoryCoreSettingsService
{
    /**
     * Product Management Settings
     */
    public static function isAutoGenerateSku(): bool
    {
        return module_setting('WMSInventoryCore', 'auto_generate_sku', true);
    }

    public static function getSkuPrefix(): string
    {
        return module_setting('WMSInventoryCore', 'sku_prefix', 'PRD');
    }

    public static function requireProductImages(): bool
    {
        return module_setting('WMSInventoryCore', 'require_product_images', false);
    }

    public static function allowNegativeStock(): bool
    {
        return module_setting('WMSInventoryCore', 'allow_negative_stock', false);
    }

    /**
     * Stock Tracking Settings
     */
    public static function getDefaultStockMethod(): string
    {
        return module_setting('WMSInventoryCore', 'default_stock_method', 'fifo');
    }

    public static function enableBatchTracking(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_batch_tracking', false);
    }

    public static function enableSerialTracking(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_serial_tracking', false);
    }

    public static function enableExpiryTracking(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_expiry_tracking', false);
    }

    /**
     * Warehouse Operations Settings
     */
    public static function getDefaultWarehouseId(): ?int
    {
        $id = module_setting('WMSInventoryCore', 'default_warehouse_id', null);

        return $id ? (int) $id : null;
    }

    public static function enableMultiLocation(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_multi_location', false);
    }

    public static function requireLocationForStock(): bool
    {
        return module_setting('WMSInventoryCore', 'require_location_for_stock', false);
    }

    public static function autoCreateBins(): bool
    {
        return module_setting('WMSInventoryCore', 'auto_create_bins', true);
    }

    /**
     * Inventory Alerts Settings
     */
    public static function enableLowStockAlerts(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_low_stock_alerts', true);
    }

    public static function enableExpiryAlerts(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_expiry_alerts', true);
    }

    public static function getExpiryAlertDays(): int
    {
        return (int) module_setting('WMSInventoryCore', 'expiry_alert_days', 30);
    }

    /**
     * Adjustments Settings
     */
    public static function requireApprovalForAdjustments(): bool
    {
        return module_setting('WMSInventoryCore', 'require_approval_for_adjustments', true);
    }

    public static function requireReasonForAdjustments(): bool
    {
        return module_setting('WMSInventoryCore', 'require_reason_for_adjustments', true);
    }

    /**
     * Transfers Settings
     */
    public static function requireApprovalForTransfers(): bool
    {
        return module_setting('WMSInventoryCore', 'require_approval_for_transfers', false);
    }

    public static function autoReceiveTransfers(): bool
    {
        return module_setting('WMSInventoryCore', 'auto_receive_transfers', false);
    }

    public static function enableInTransitTracking(): bool
    {
        return module_setting('WMSInventoryCore', 'enable_in_transit_tracking', true);
    }

    /**
     * Generate next SKU based on settings
     */
    public static function generateNextSku(): string
    {
        $prefix = self::getSkuPrefix();
        $lastProduct = \Modules\WMSInventoryCore\app\Models\Product::where('sku', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(sku) DESC')
            ->orderBy('sku', 'desc')
            ->first();

        if ($lastProduct && preg_match('/^'.preg_quote($prefix).'(\d+)$/', $lastProduct->sku, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
