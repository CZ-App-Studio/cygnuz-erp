<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdjustmentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'effect', // increase, decrease, or transfer
        'description',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Get the adjustments with this type.
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(Adjustment::class);
    }

    /**
     * Get the user who created this adjustment type.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this adjustment type.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Scope a query to only include active adjustment types.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include increase types.
     */
    public function scopeIncrease($query)
    {
        return $query->where('effect', 'increase');
    }

    /**
     * Scope a query to only include decrease types.
     */
    public function scopeDecrease($query)
    {
        return $query->where('effect', 'decrease');
    }

    /**
     * Scope a query to only include transfer types.
     */
    public function scopeTransfer($query)
    {
        return $query->where('effect', 'transfer');
    }
}
