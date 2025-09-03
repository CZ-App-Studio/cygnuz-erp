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

class Purchase extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'purchases';

    protected $fillable = [
        'date',
        'code',
        'reference_no',
        'invoice_no',
        'vendor_id',
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
        'expected_delivery_date',
        'actual_delivery_date',
        'shipping_method',
        'tracking_number',
        'approval_status',
        'approved_by_id',
        'approved_at',
        'received_by_id',
        'received_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'date' => 'date',
        'payment_due_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the vendor associated with this purchase.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the warehouse associated with this purchase.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the products in this purchase.
     */
    public function products(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class);
    }

    /**
     * Get the attachments for this purchase.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(PurchaseAttachment::class);
    }

    /**
     * Get the user who approved this purchase.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'approved_by_id');
    }

    /**
     * Get the user who received this purchase.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'received_by_id');
    }

    /**
     * Get the remaining balance to pay.
     */
    public function getRemainingBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if this purchase is fully paid.
     */
    public function isFullyPaid()
    {
        return $this->payment_status === 'paid' || $this->remaining_balance <= 0;
    }

    /**
     * Check if this purchase is fully received.
     */
    public function isFullyReceived()
    {
        foreach ($this->products as $purchaseProduct) {
            if (! $purchaseProduct->is_fully_received) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the percentage of items received.
     */
    public function getReceivingProgressAttribute()
    {
        $totalItems = $this->products->count();
        if ($totalItems === 0) {
            return 0;
        }

        $receivedItems = $this->products->where('is_fully_received', true)->count();

        return ($receivedItems / $totalItems) * 100;
    }
}
