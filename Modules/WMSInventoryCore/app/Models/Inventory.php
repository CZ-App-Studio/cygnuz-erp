<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Inventory extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'inventories';

    protected $fillable = [
        'warehouse_id',
        'product_id',
        'stock_level',
        'reserved_quantity',
        'unit_id',
        'weight',
        'cost_price',
        'damaged_quantity',
        'quarantine_quantity',
        'in_transit_quantity',
        'last_count_date',
        'last_movement_date',
        'low_stock_alert_sent',
        'last_alert_sent_at',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'stock_level' => 'integer',
        'reserved_quantity' => 'integer',
        'available_quantity' => 'integer',
        'weight' => 'decimal:2',
        'cost_price' => 'decimal:4',
        'value' => 'decimal:2',
        'damaged_quantity' => 'integer',
        'quarantine_quantity' => 'integer',
        'in_transit_quantity' => 'integer',
        'last_count_date' => 'date',
        'last_movement_date' => 'date',
        'low_stock_alert_sent' => 'boolean',
        'last_alert_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the warehouse associated with this inventory.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the product associated with this inventory.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit associated with this inventory.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Check if this inventory is below the product's reorder point.
     */
    public function isBelowReorderPoint()
    {
        if (!$this->product->reorder_point) return false;

        return $this->stock_level <= $this->product->reorder_point;
    }

    /**
     * Check if this inventory is below the product's min stock level.
     */
    public function isBelowMinStock()
    {
        if (!$this->product->min_stock_level) return false;

        return $this->stock_level <= $this->product->min_stock_level;
    }

    /**
     * Check if this inventory is above the product's max stock level.
     */
    public function isAboveMaxStock()
    {
        if (!$this->product->max_stock_level) return false;

        return $this->stock_level >= $this->product->max_stock_level;
    }

    /**
     * Get the health status of this inventory.
     */
    public function getHealthStatusAttribute()
    {
        if ($this->isBelowMinStock()) {
            return 'low';
        } elseif ($this->isAboveMaxStock()) {
            return 'overstocked';
        } elseif ($this->isBelowReorderPoint()) {
            return 'reorder';
        } else {
            return 'healthy';
        }
    }

    /**
     * Get the quality status of this inventory.
     */
    public function getQualityStatusAttribute()
    {
        $total = $this->stock_level;
        $damaged = $this->damaged_quantity;
        $quarantine = $this->quarantine_quantity;

        if ($damaged > 0 || $quarantine > 0) {
            $problemPercentage = (($damaged + $quarantine) / $total) * 100;

            if ($problemPercentage >= 50) {
                return 'critical';
            } elseif ($problemPercentage >= 20) {
                return 'warning';
            } else {
                return 'acceptable';
            }
        }

        return 'good';
    }
}
