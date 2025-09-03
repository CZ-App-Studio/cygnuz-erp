<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Unit extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'units';

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\WMSInventoryCore\Database\Factories\UnitFactory::new();
    }

    protected $fillable = [
        'name',
        'code',
        'description',
        'base_unit_id',
        'operator',
        'operation_value',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected function casts(): array
    {
        return [
            'operation_value' => 'decimal:4',
            'status' => 'string',
        ];
    }

    /**
     * Get the products that use this unit.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the base unit if this is a derived unit.
     */
    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    /**
     * Get the derived units of this unit.
     */
    public function derivedUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    /**
     * Check if this unit is a base unit.
     */
    public function isBaseUnit()
    {
        return $this->base_unit_id === null;
    }

    /**
     * Convert a quantity from this unit to its base unit.
     */
    public function convertToBaseUnit($quantity)
    {
        if ($this->isBaseUnit()) {
            return $quantity;
        }

        if ($this->operator === '*') {
            return $quantity * $this->operation_value;
        } else {
            return $quantity / $this->operation_value;
        }
    }

    /**
     * Convert a quantity from base unit to this unit.
     */
    public function convertFromBaseUnit($quantity)
    {
        if ($this->isBaseUnit()) {
            return $quantity;
        }

        if ($this->operator === '*') {
            return $quantity / $this->operation_value;
        } else {
            return $quantity * $this->operation_value;
        }
    }

    /**
     * Scope a query to only include active units.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
