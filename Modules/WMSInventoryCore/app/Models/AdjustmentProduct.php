<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdjustmentProduct extends Model
{
    protected $fillable = [
        'adjustment_id',
        'product_id',
        'quantity',
        'unit_cost',
        'subtotal',
        'reason',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    /**
     * Get the adjustment that owns this product.
     */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class);
    }

    /**
     * Get the product being adjusted.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if this adjustment increases inventory.
     */
    public function isIncreasing()
    {
        return $this->adjustment && $this->adjustment->isIncreasing();
    }

    /**
     * Check if this adjustment decreases inventory.
     */
    public function isDecreasing()
    {
        return $this->adjustment && $this->adjustment->isDecreasing();
    }
}
