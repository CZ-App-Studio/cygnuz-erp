<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Warehouse extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'warehouses';

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone_number',
        'email',
        'contact_person',
        'contact_name',
        'contact_email',
        'contact_phone',
        'alternate_phone',
        'logo',
        'latitude',
        'longitude',
        'total_area',
        'storage_capacity',
        'max_weight_capacity',
        'shelf_count',
        'rack_count',
        'bin_count',
        'opening_time',
        'closing_time',
        'is_24_hours',
        'operating_days',
        'warehouse_type',
        'is_main',
        'allow_negative_inventory',
        'requires_approval',
        'status',
        'is_active',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_area' => 'decimal:2',
        'storage_capacity' => 'decimal:2',
        'max_weight_capacity' => 'decimal:2',
        'shelf_count' => 'integer',
        'rack_count' => 'integer',
        'bin_count' => 'integer',
        'opening_time' => 'datetime',
        'closing_time' => 'datetime',
        'is_24_hours' => 'boolean',
        'is_active' => 'boolean',
        'operating_days' => 'array',
        'is_main' => 'boolean',
        'allow_negative_inventory' => 'boolean',
        'requires_approval' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the inventories in this warehouse.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the zones in this warehouse.
     */
    public function zones(): HasMany
    {
        return $this->hasMany(WarehouseZone::class);
    }

    /**
     * Get the bin locations in this warehouse.
     */
    public function binLocations(): HasMany
    {
        return $this->hasMany(BinLocation::class);
    }

    /**
     * Get the purchases for this warehouse.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the sales from this warehouse.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Get the product batches in this warehouse.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute()
    {
        $parts = [$this->address];

        if ($this->city) {
            $parts[] = $this->city;
        }
        if ($this->state) {
            $parts[] = $this->state;
        }
        if ($this->postal_code) {
            $parts[] = $this->postal_code;
        }
        if ($this->country) {
            $parts[] = $this->country;
        }

        return implode(', ', $parts);
    }

    /**
     * Check if warehouse is currently open.
     */
    public function isCurrentlyOpen()
    {
        if ($this->is_24_hours) {
            return true;
        }

        if (empty($this->operating_days)) {
            return true;
        }

        $currentDayOfWeek = now()->dayOfWeek;
        if (! in_array($currentDayOfWeek, $this->operating_days)) {
            return false;
        }

        if ($this->opening_time && $this->closing_time) {
            $currentTime = now()->format('H:i:s');
            $openingTime = date('H:i:s', strtotime($this->opening_time));
            $closingTime = date('H:i:s', strtotime($this->closing_time));

            return $currentTime >= $openingTime && $currentTime <= $closingTime;
        }

        return true;
    }
}
