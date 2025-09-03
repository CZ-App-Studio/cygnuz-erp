<?php

namespace Modules\WMSInventoryCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Option extends Model
{
    protected $fillable = [
        'name',
        'variant_id',
        'status',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Get the variant that owns this option.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    /**
     * Get the user who created this option.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this option.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Scope a query to only include active options.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
