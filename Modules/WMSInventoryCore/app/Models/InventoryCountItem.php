<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountItem extends Model
{
    protected $fillable = [
        'inventory_count_id',
        'product_id',
        'bin_location_id',
        'batch_id',
        'expected_quantity',
        'counted_quantity',
        'difference',
        'unit_id',
        'status',
        'is_adjusted',
        'adjustment_id',
        'counted_by_id'
    ];

    protected $casts = [
        'expected_quantity' => 'integer',
        'counted_quantity' => 'integer',
        'difference' => 'integer',
        'is_adjusted' => 'boolean'
    ];

    /**
     * Get the inventory count this item belongs to.
     */
    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(InventoryCount::class);
    }

    /**
     * Get the product being counted.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the bin location where the product is stored.
     */
    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class);
    }

    /**
     * Get the batch being counted.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * Get the unit of measure.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the adjustment record if this count resulted in an adjustment.
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class);
    }

    /**
     * Get the user who counted this item.
     */
    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'counted_by_id');
    }

    /**
     * Calculate the variance percentage.
     */
    public function getVariancePercentageAttribute()
    {
        if (!$this->expected_quantity) return 0;

        return ($this->difference / $this->expected_quantity) * 100;
    }
}
