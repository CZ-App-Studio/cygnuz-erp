<?php

namespace Modules\SystemCore\app\Models;

use App\Models\User;
use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserSettings extends Model implements AuditableContract
{
    use Auditable, UserActionsTrait;

    protected $table = 'user_settings';

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'tenant_id',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'created_by_id' => 'integer',
        'updated_by_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a specific setting for a user
     */
    public static function getSetting($userId, $key, $default = null)
    {
        $setting = self::where('user_id', $userId)->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a specific setting for a user
     */
    public static function setSetting($userId, $key, $value)
    {
        return self::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get all settings for a user
     */
    public static function getUserSettings($userId)
    {
        return self::where('user_id', $userId)->pluck('value', 'key')->toArray();
    }

    /**
     * Set multiple settings for a user
     */
    public static function setMultipleSettings($userId, array $settings)
    {
        foreach ($settings as $key => $value) {
            self::setSetting($userId, $key, $value);
        }
    }
}
