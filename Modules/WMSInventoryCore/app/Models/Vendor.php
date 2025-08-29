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

class Vendor extends Model implements Auditable
{
    use HasFactory, SoftDeletes, UserActionsTrait, AuditableTrait;

    protected $table = 'vendors';
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone_number',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'tax_number',
        'website',
        'status',
        'notes',
        'payment_terms',
        'lead_time_days',
        'minimum_order_value',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'minimum_order_value' => 'decimal:2',
        'status' => 'string',
        'is_active' => 'boolean'
    ];

    /**
     * Get the purchases from this vendor.
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Get the user who created this vendor.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this vendor.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute()
    {
        $parts = [$this->address];

        if ($this->city) $parts[] = $this->city;
        if ($this->state) $parts[] = $this->state;
        if ($this->postal_code) $parts[] = $this->postal_code;
        if ($this->country) $parts[] = $this->country;

        return implode(', ', $parts);
    }

    /**
     * Scope a query to only include active vendors.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Calculate the total purchase amount from this vendor.
     */
    public function getTotalPurchasesAttribute()
    {
        return $this->purchases->sum('total_amount');
    }

    /**
     * Calculate the outstanding balance to this vendor.
     */
    public function getOutstandingBalanceAttribute()
    {
        return $this->purchases->sum(function($purchase) {
            return $purchase->total_amount - $purchase->paid_amount;
        });
    }
}
