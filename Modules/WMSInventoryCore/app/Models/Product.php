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

class Product extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'code',
        'description',
        'sku',
        'barcode',
        'additional_barcodes',
        'image',
        'unit_id',
        'category_id',
        'track_weight',
        'track_quantity',
        'track_serial_number',
        'track_batch',
        'track_expiry',
        'alert_on',
        'cost_price',
        'selling_price',
        'min_stock_level',
        'max_stock_level',
        'reorder_point',
        'safety_stock',
        'weight',
        'width',
        'height',
        'length',
        'lead_time_days',
        'is_returnable',
        'is_purchasable',
        'is_sellable',
        'status',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'track_weight' => 'boolean',
        'track_quantity' => 'boolean',
        'track_serial_number' => 'boolean',
        'track_batch' => 'boolean',
        'track_expiry' => 'boolean',
        'alert_on' => 'decimal:2',
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'reorder_point' => 'decimal:2',
        'safety_stock' => 'decimal:2',
        'weight' => 'decimal:3',
        'width' => 'decimal:3',
        'height' => 'decimal:3',
        'length' => 'decimal:3',
        'is_returnable' => 'boolean',
        'is_purchasable' => 'boolean',
        'is_sellable' => 'boolean',
        'additional_barcodes' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the unit associated with this product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the category associated with this product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the variants for this product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * Get the inventory records for this product.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the inventory relation with compatibility for older code.
     * This is an alias for inventories() that returns the same relationship.
     */
    public function inventory(): HasMany
    {
        return $this->inventories();
    }

    /**
     * Get the batches for this product.
     */
    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    /**
     * Get the bin locations for this product.
     */
    public function binLocations(): HasMany
    {
        return $this->hasMany(ProductBinLocation::class);
    }

    /**
     * Get the prices for this product.
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Get stock for a specific warehouse.
     */
    public function getStockInWarehouse($warehouseId)
    {
        $inventory = $this->inventories()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $inventory ? $inventory->stock_level : 0;
    }

    /**
     * Check if product is low on stock.
     */
    public function isLowOnStock()
    {
        if (!$this->reorder_point)
            return false;

        foreach ($this->inventories as $inventory) {
            if ($inventory->stock_level <= $this->reorder_point) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get available stock (stock minus reserved).
     */
    public function getAvailableStockInWarehouse($warehouseId)
    {
        $inventory = $this->inventories()
            ->where('warehouse_id', $warehouseId)
            ->first();

        return $inventory ? $inventory->available_quantity : 0;
    }

    /**
     * Get current stock for a specific warehouse.
     * Alias for getStockInWarehouse for backward compatibility.
     */
    public function getCurrentStock($warehouseId)
    {
        return $this->getStockInWarehouse($warehouseId);
    }
}
