<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingHistory extends Model
{
    protected $table = 'settings_history';
    
    public $timestamps = false;
    
    protected $fillable = [
        'setting_type',
        'setting_key',
        'module',
        'old_value',
        'new_value',
        'changed_by',
        'changed_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    /**
     * The user who made the change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the decoded old value
     */
    public function getOldValueAttribute($value)
    {
        return $this->decodeValue($value);
    }

    /**
     * Get the decoded new value
     */
    public function getNewValueAttribute($value)
    {
        return $this->decodeValue($value);
    }

    /**
     * Decode value if it's JSON
     */
    private function decodeValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Log a setting change
     */
    public static function logChange(
        string $type,
        string $key,
        $oldValue,
        $newValue,
        ?string $module = null
    ): self {
        return static::create([
            'setting_type' => $type,
            'setting_key' => $key,
            'module' => $module,
            'old_value' => is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) || is_object($newValue) ? json_encode($newValue) : $newValue,
            'changed_by' => auth()->id() ?? 1,
            'changed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Scope for system settings
     */
    public function scopeSystemSettings($query)
    {
        return $query->where('setting_type', 'system');
    }

    /**
     * Scope for module settings
     */
    public function scopeModuleSettings($query, ?string $module = null)
    {
        $query = $query->where('setting_type', 'module');
        
        if ($module) {
            $query->where('module', $module);
        }
        
        return $query;
    }

    /**
     * Scope for specific key
     */
    public function scopeForKey($query, string $key)
    {
        return $query->where('setting_key', $key);
    }
}