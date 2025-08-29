<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserDevice extends Model
{
    use SoftDeletes, UserActionsTrait;

    protected $table = 'user_devices';

    protected $fillable = [
        'user_id',
        'device_type',
        'device_id',
        'brand',
        'board',
        'sdk_version',
        'model',
        'token',
        'status',
        'last_login_at',
        'activated_at',
        'failed_attempts',
        'blocked_until',
        'blocked_reason',
        'app_version',
        'battery_percentage',
        'is_charging',
        'is_online',
        'is_gps_on',
        'is_wifi_on',
        'is_mock',
        'signal_strength',
        'latitude',
        'longitude',
        'bearing',
        'horizontalAccuracy',
        'altitude',
        'verticalAccuracy',
        'course',
        'courseAccuracy',
        'speed',
        'speedAccuracy',
        'ip_address',
        'address',
        'created_by_id',
        'updated_by_id',
        'tenant_id',
    ];

    protected $casts = [
        'is_charging' => 'boolean',
        'is_online' => 'boolean',
        'is_gps_on' => 'boolean',
        'is_wifi_on' => 'boolean',
        'is_mock' => 'boolean',
        'battery_percentage' => 'integer',
        'signal_strength' => 'integer',
        'failed_attempts' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
        'bearing' => 'float',
        'horizontalAccuracy' => 'float',
        'altitude' => 'float',
        'verticalAccuracy' => 'float',
        'course' => 'float',
        'courseAccuracy' => 'float',
        'speed' => 'float',
        'speedAccuracy' => 'float',
        'last_login_at' => 'datetime',
        'activated_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Check if device is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if device is blocked
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked' ||
               ($this->blocked_until && $this->blocked_until->isFuture());
    }

    /**
     * Check if device is activated
     */
    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for user's active device
     */
    public function scopeUserActive($query, $userId)
    {
        return $query->where('user_id', $userId)->where('status', 'active');
    }
}
