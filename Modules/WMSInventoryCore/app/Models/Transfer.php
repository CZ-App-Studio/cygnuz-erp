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

class Transfer extends Model implements Auditable
{
    use AuditableTrait, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'transfers';

    protected $fillable = [
        'transfer_date',
        'code',
        'reference_no',
        'source_warehouse_id',
        'destination_warehouse_id',
        'notes',
        'status',
        'shipping_cost',
        'shipping_method',
        'tracking_number',
        'expected_arrival_date',
        'actual_arrival_date',
        'approved_by_id',
        'approved_at',
        'shipped_by_id',
        'shipped_at',
        'received_by_id',
        'received_at',
        'shipping_notes',
        'receiving_notes',
        'cancellation_reason',
        'cancelled_by_id',
        'cancelled_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'expected_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
        'approved_at' => 'datetime',
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'shipping_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the source warehouse.
     */
    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    /**
     * Get the destination warehouse.
     */
    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    /**
     * Get the products in this transfer.
     */
    public function products(): HasMany
    {
        return $this->hasMany(TransferProduct::class);
    }

    /**
     * Get the user who approved this transfer.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'approved_by_id');
    }

    /**
     * Get the user who shipped this transfer.
     */
    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'shipped_by_id');
    }

    /**
     * Get the user who received this transfer.
     */
    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'received_by_id');
    }

    /**
     * Get the user who cancelled this transfer.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'cancelled_by_id');
    }

    /**
     * Get the user who created this transfer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this transfer.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Check if this transfer is fully shipped.
     */
    public function isFullyShipped()
    {
        return $this->status === 'shipped' || $this->status === 'delivered';
    }

    /**
     * Check if this transfer is fully received.
     */
    public function isFullyReceived()
    {
        return $this->status === 'delivered';
    }

    /**
     * Get the shipping progress percentage.
     */
    public function getShippingProgressAttribute()
    {
        if ($this->isFullyShipped()) {
            return 100;
        }

        $totalItems = $this->products->count();
        if ($totalItems === 0) {
            return 0;
        }

        $shippedItems = $this->products->where('is_shipped', true)->count();

        return ($shippedItems / $totalItems) * 100;
    }

    /**
     * Get the receiving progress percentage.
     */
    public function getReceivingProgressAttribute()
    {
        if ($this->isFullyReceived()) {
            return 100;
        }

        $totalItems = $this->products->count();
        if ($totalItems === 0) {
            return 0;
        }

        $receivedItems = $this->products->where('is_received', true)->count();

        return ($receivedItems / $totalItems) * 100;
    }
}
