<?php

namespace Modules\WMSInventoryCore\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable;

class Adjustment extends Model implements \OwenIt\Auditing\Contracts\Auditable
{
    use Auditable, HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'adjustments';

    protected $fillable = [
        'date',
        'code',
        'reference_no',
        'warehouse_id',
        'adjustment_type_id',
        'reason',
        'notes',
        'total_amount',
        'status',
        'approved_by_id',
        'approved_at',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the warehouse associated with this adjustment.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the adjustment type.
     */
    public function adjustmentType(): BelongsTo
    {
        return $this->belongsTo(AdjustmentType::class);
    }

    /**
     * Get the products in this adjustment.
     */
    public function products(): HasMany
    {
        return $this->hasMany(AdjustmentProduct::class);
    }

    /**
     * Get the user who approved this adjustment.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'approved_by_id');
    }

    /**
     * Get the user who created this adjustment.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this adjustment.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Check if this adjustment increases inventory.
     */
    public function isIncreasing()
    {
        return $this->adjustmentType && $this->adjustmentType->effect === 'increase';
    }

    /**
     * Check if this adjustment decreases inventory.
     */
    public function isDecreasing()
    {
        return $this->adjustmentType && $this->adjustmentType->effect === 'decrease';
    }
}
