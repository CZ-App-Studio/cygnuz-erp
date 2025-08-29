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

class Sale extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'sales';

    protected $fillable = [
        'date',
        'code',
        'reference_no',
        'invoice_no',
        'order_no',
        'customer_id',
        'warehouse_id',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'shipping_cost',
        'total_amount',
        'notes',
        'status',
        'payment_status',
        'payment_terms',
        'payment_due_date',
        'paid_amount',
        'shipping_address',
        'shipping_method',
        'tracking_number',
        'expected_delivery_date',
        'actual_delivery_date',
        'fulfillment_status',
        'fulfilled_by_id',
        'fulfilled_at',
        'sales_person_id',
        'profit_margin',
        'total_cost',
        'total_profit',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'date' => 'date',
        'payment_due_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'fulfilled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Get the customer associated with this sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\CRMCore\app\Models\Customer::class);
    }

    /**
     * Get the warehouse associated with this sale.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the products in this sale.
     */
    public function products(): HasMany
    {
        return $this->hasMany(SaleProduct::class);
    }

    /**
     * Get the attachments for this sale.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SaleAttachment::class);
    }

    /**
     * Get the user who fulfilled this sale.
     */
    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'fulfilled_by_id');
    }

    /**
     * Get the sales person.
     */
    public function salesPerson(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'sales_person_id');
    }

    /**
     * Get the remaining balance to be paid.
     */
    public function getRemainingBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if this sale is fully paid.
     */
    public function isFullyPaid()
    {
        return $this->payment_status === 'paid' || $this->remaining_balance <= 0;
    }

    /**
     * Check if this sale is fully fulfilled.
     */
    public function isFullyFulfilled()
    {
        foreach ($this->products as $saleProduct) {
            if (!$saleProduct->is_fully_fulfilled) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the percentage of items fulfilled.
     */
    public function getFulfillmentProgressAttribute()
    {
        $totalItems = $this->products->count();
        if ($totalItems === 0) return 0;

        $fulfilledItems = $this->products->where('is_fully_fulfilled', true)->count();
        return ($fulfilledItems / $totalItems) * 100;
    }
}
