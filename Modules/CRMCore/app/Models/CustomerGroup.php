<?php

namespace Modules\CRMCore\app\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerGroup extends Model implements AuditableContract
{
    use SoftDeletes, UserActionsTrait, AuditableTrait, HasFactory;

    protected $table = 'customer_groups';

    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_percentage',
        'priority',
        'credit_limit',
        'payment_terms',
        'priority_support',
        'free_shipping',
        'min_order_amount',
        'is_active',
        'tenant_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'priority_support' => 'boolean',
        'free_shipping' => 'boolean',
        'min_order_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get customers in this group
     */
    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Scope for active groups
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}