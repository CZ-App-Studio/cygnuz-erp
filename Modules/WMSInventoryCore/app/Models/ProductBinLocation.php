<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBinLocation extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'bin_location_id',
        'quantity',
        'unit_id',
        'batch_id'
    ];

    /**
     * Get the product associated with this bin location.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse associated with this bin location.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the bin location.
     */
    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class);
    }

    /**
     * Get the unit associated with this product bin location.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the batch associated with this product bin location.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
