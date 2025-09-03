<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'batch_id',
        'bin_location_id',
        'quantity',
        'unit_id',
        'reservation_type',
        'reference_id',
        'reference_type',
        'reserved_until',
        'status',
        'created_by_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_until' => 'datetime',
    ];

    /**
     * Get the warehouse associated with this reservation.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product associated with this reservation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the batch associated with this reservation.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * Get the bin location associated with this reservation.
     */
    public function binLocation(): BelongsTo
    {
        return $this->belongsTo(BinLocation::class);
    }

    /**
     * Get the unit associated with this reservation.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the user who created this reservation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the related reference model based on the reference type.
     */
    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include active reservations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include non-expired reservations.
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('reserved_until')
                ->orWhere('reserved_until', '>=', now());
        });
    }
}
