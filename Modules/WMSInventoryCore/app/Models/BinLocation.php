<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BinLocation extends Model
{
    protected $fillable = [
        'warehouse_id',
        'zone_id',
        'name',
        'code',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'max_weight',
        'max_volume',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'max_weight' => 'decimal:2',
        'max_volume' => 'decimal:2',
    ];

    /**
     * Get the warehouse associated with this bin location.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the zone associated with this bin location.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(WarehouseZone::class, 'zone_id');
    }

    /**
     * Get the products stored in this bin location.
     */
    public function productBinLocations(): HasMany
    {
        return $this->hasMany(ProductBinLocation::class);
    }

    /**
     * Get the user who created this bin location.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this bin location.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Scope a query to only include active bin locations.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the full location code for display.
     */
    public function getFullLocationAttribute()
    {
        $parts = [];

        if ($this->aisle) {
            $parts[] = "A:{$this->aisle}";
        }
        if ($this->rack) {
            $parts[] = "R:{$this->rack}";
        }
        if ($this->shelf) {
            $parts[] = "S:{$this->shelf}";
        }
        if ($this->bin) {
            $parts[] = "B:{$this->bin}";
        }

        return implode('-', $parts);
    }
}
