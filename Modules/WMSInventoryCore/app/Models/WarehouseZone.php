<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseZone extends Model
{
    protected $fillable = [
        'warehouse_id',
        'name',
        'code',
        'description',
        'zone_type',
        'status',
        'created_by_id',
        'updated_by_id'
    ];

    /**
     * Get the warehouse associated with this zone.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the bin locations associated with this zone.
     */
    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class, 'zone_id');
    }

    /**
     * Get the user who created this zone.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this zone.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Scope a query to only include active zones.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
