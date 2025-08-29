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

class ProductBatch extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'product_batches';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'batch_number',
        'lot_number',
        'manufacturing_date',
        'expiry_date',
        'quantity',
        'unit_id',
        'weight',
        'cost_price',
        'status',
        'purchase_id',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the product associated with this batch.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the warehouse associated with this batch.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the unit associated with this batch.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the purchase that created this batch.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the bin locations where this batch is stored.
     */
    public function binLocations(): HasMany
    {
        return $this->hasMany(ProductBinLocation::class, 'batch_id');
    }

    /**
     * Scope a query to only include active batches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include non-expired batches.
     */
    public function scopeNotExpired($query, $date = null)
    {
        $date = $date ?: now()->toDateString();

        return $query->where(function($q) use ($date) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>=', $date);
        });
    }

    /**
     * Scope a query to only include batches with stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
