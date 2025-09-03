<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCount extends Model
{
    protected $fillable = [
        'reference_no',
        'count_date',
        'warehouse_id',
        'zone_id',
        'count_type',
        'status',
        'notes',
        'started_at',
        'completed_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'count_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the warehouse associated with this inventory count.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone associated with this inventory count.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    /**
     * Get the inventory count items for this count.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryCountItem::class);
    }

    /**
     * Get the user who created this inventory count.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this inventory count.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Calculate the total count variance.
     */
    public function getTotalVarianceAttribute()
    {
        return $this->items->sum('difference');
    }

    /**
     * Calculate the percentage of items counted.
     */
    public function getCountProgressAttribute()
    {
        $totalItems = $this->items->count();
        if ($totalItems === 0) {
            return 0;
        }

        $countedItems = $this->items->whereNotNull('counted_quantity')->count();

        return ($countedItems / $totalItems) * 100;
    }
}
