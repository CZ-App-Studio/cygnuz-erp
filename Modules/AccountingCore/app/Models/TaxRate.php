<?php

namespace Modules\AccountingCore\app\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, SoftDeletes, UserActionsTrait;

    protected $table = 'tax_rates';

    protected $fillable = [
        'name',
        'rate',
        'type',
        'is_default',
        'is_active',
        'description',
        'tax_authority',
        'created_by_id',
        'updated_by_id',
        'tenant_id',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to get active tax rates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default tax rate.
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->where('is_active', true)->first();
    }

    /**
     * Get active tax rates.
     */
    public static function getActive()
    {
        return static::where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Set this tax rate as default (and unset others).
     */
    public function setAsDefault()
    {
        // Remove default from all other tax rates
        static::where('is_default', true)->update(['is_default' => false]);

        // Set this one as default
        $this->update(['is_default' => true]);
    }

    /**
     * Format the rate based on type.
     */
    public function getFormattedRateAttribute()
    {
        if ($this->type === 'fixed') {
            return number_format($this->rate, 2);
        }

        return number_format($this->rate, 2).'%';
    }

    /**
     * Check if this tax rate is in use
     */
    public function isInUse()
    {
        // For now, return false since we don't have tax_rate_id columns in other tables yet
        // This can be expanded later when tax rates are integrated into transactions
        return false;
    }

    /**
     * Calculate tax amount
     */
    public function calculateTax($amount)
    {
        if ($this->type === 'percentage') {
            return ($amount * $this->rate) / 100;
        } else {
            return $this->rate;
        }
    }
}
