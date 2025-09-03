<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class PurchaseProduct extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'purchase_products';

    protected $fillable = [
        'purchase_id',
        'product_id',
        'quantity',
        'unit_id',
        'batch_id',
        'expiry_date',
        'batch_number',
        'lot_number',
        'weight',
        'unit_cost',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'subtotal',
        'received_quantity',
        'is_fully_received',
        'accepted_quantity',
        'rejected_quantity',
        'rejection_reason',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'weight' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'received_quantity' => 'integer',
        'is_fully_received' => 'boolean',
        'accepted_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the purchase that owns this product.
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Get the product being purchased.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the unit associated with this purchase product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the batch associated with this purchase product.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }

    /**
     * Calculate the remaining quantity to receive.
     */
    public function getRemainingQuantityAttribute()
    {
        return $this->quantity - $this->received_quantity;
    }

    /**
     * Calculate the receiving progress percentage.
     */
    public function getReceivingProgressAttribute()
    {
        if ($this->quantity === 0) {
            return 100;
        }

        return ($this->received_quantity / $this->quantity) * 100;
    }

    /**
     * Calculate the unit price including tax.
     */
    public function getUnitPriceWithTaxAttribute()
    {
        $taxAmount = $this->unit_cost * ($this->tax_rate / 100);

        return $this->unit_cost + $taxAmount;
    }

    /**
     * Get the user who created this purchase product.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this purchase product.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }
}
