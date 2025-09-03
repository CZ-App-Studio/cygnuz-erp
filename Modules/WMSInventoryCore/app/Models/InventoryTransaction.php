<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_id',
        'weight',
        'transaction_type',
        'reference_id',
        'reference_type',
        'notes',
        'created_by_id',
    ];

    /**
     * Get the warehouse associated with the transaction.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product associated with the transaction.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit associated with the transaction.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the creator of the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the related transaction model based on the reference type.
     */
    public function reference()
    {
        return $this->morphTo();
    }
}
