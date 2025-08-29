<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class SaleProduct extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'sale_products';

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_id',
        'batch_id',
        'weight',
        'unit_price',
        'unit_cost',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'subtotal',
        'profit',
        'fulfilled_quantity',
        'is_fully_fulfilled',
        'returned_quantity',
        'return_reason',
        'price_list_id',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'profit' => 'decimal:2',
        'fulfilled_quantity' => 'integer',
        'is_fully_fulfilled' => 'boolean',
        'returned_quantity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the sale that owns this product.
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Get the product being sold.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit associated with this sale product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the batch associated with this sale product.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * Get the price list associated with this sale product.
     */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /**
     * Calculate the remaining quantity to fulfill.
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->fulfilled_quantity;
    }

    /**
     * Calculate the fulfillment progress percentage.
     */
    public function getFulfillmentProgressAttribute()
    {
        if ($this->quantity === 0) return 100;

        return ($this->fulfilled_quantity / $this->quantity) * 100;
    }

    /**
     * Calculate the profit margin percentage.
     */
    public function getProfitMarginAttribute()
    {
        if ($this->subtotal == 0) return 0;

        return ($this->profit / $this->subtotal) * 100;
    }

    /**
     * Calculate the unit price including tax.
     */
    public function getUnitPriceWithTaxAttribute()
    {
        $taxAmount = $this->unit_price * ($this->tax_rate / 100);
        return $this->unit_price + $taxAmount;
    }
}
