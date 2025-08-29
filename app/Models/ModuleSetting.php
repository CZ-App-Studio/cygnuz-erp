<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ModuleSetting extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = [
        'module',
        'key',
        'value',
        'type',
        'description',
    ];

    /**
     * Get the actual value based on the type
     */
    public function getValueAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }

        return match ($this->type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Set the value based on the type
     */
    public function setValueAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['value'] = null;
            return;
        }

        $this->attributes['value'] = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Clear cache when settings are updated
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saved(function ($model) {
            Cache::forget("module_settings_{$model->module}");
            Cache::forget('module_settings_all');
            Cache::forget('global_settings');
        });
        
        static::deleted(function ($model) {
            Cache::forget("module_settings_{$model->module}");
            Cache::forget('module_settings_all');
            Cache::forget('global_settings');
        });
    }

    /**
     * Scope for module
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get all settings grouped by module
     */
    public static function getAllGrouped()
    {
        return static::all()->groupBy('module')->map(function ($items) {
            return $items->pluck('value', 'key');
        });
    }
}