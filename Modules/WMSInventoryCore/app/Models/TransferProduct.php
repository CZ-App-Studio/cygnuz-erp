<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class TransferProduct extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'transfer_products';

    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'unit_id',
        'batch_id',
        'weight',
        'source_bin_location_id',
        'destination_bin_location_id',
        'shipped_quantity',
        'received_quantity',
        'is_shipped',
        'is_received',
        'notes',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'shipped_quantity' => 'integer',
        'received_quantity' => 'integer',
        'is_shipped' => 'boolean',
        'is_received' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the transfer that owns this product.
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Get the product being transferred.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit associated with this transfer product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the batch associated with this transfer product.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * Get the source bin location.
     */
    public function sourceBinLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'source_bin_location_id');
    }

    /**
     * Get the destination bin location.
     */
    public function destinationBinLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class, 'destination_bin_location_id');
    }

    /**
     * Calculate the remaining quantity to ship.
     */
    public function getRemainingToShipAttribute()
    {
        return $this->quantity - $this->shipped_quantity;
    }

    /**
     * Calculate the remaining quantity to receive.
     */
    public function getRemainingToReceiveAttribute()
    {
        return $this->shipped_quantity - $this->received_quantity;
    }

    /**
     * Calculate the shipping progress percentage.
     */
    public function getShippingProgressAttribute()
    {
        if ($this->quantity === 0) return 100;

        return ($this->shipped_quantity / $this->quantity) * 100;
    }

    /**
     * Calculate the receiving progress percentage.
     */
    public function getReceivingProgressAttribute()
    {
        if ($this->shipped_quantity === 0) return 0;

        return ($this->received_quantity / $this->shipped_quantity) * 100;
    }
}
