<?php

namespace App\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use HasFactory, UserActionsTrait;

    protected $fillable = ['user_id', 'preferences'];

    protected $casts = [
        'preferences' => 'array',
    ];

    /**
     * Get the user that owns the notification preference
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default HR notification preferences
     */
    public static function getDefaultHRPreferences(): array
    {
        return [
            'hrcore' => [
                'leave' => [
                    'email' => true,
                    'push' => true,
                    'reminder_email' => true,
                    'reminder_push' => true,
                    'balance_updates_email' => false,
                    'balance_updates_push' => false,
                ],
                'attendance' => [
                    'late_checkin_email' => false,
                    'late_checkin_push' => true,
                    'missing_checkout_email' => true,
                    'missing_checkout_push' => true,
                    'regularization_email' => true,
                    'regularization_push' => true,
                ],
                'expense' => [
                    'email' => true,
                    'push' => true,
                ],
                'employee' => [
                    'onboarding_push' => true,
                    'profile_changes_email' => true,
                    'profile_changes_push' => true,
                ],
                'holiday' => [
                    'announcement_email' => true,
                    'announcement_push' => true,
                ],
            ],
        ];
    }

    /**
     * Update or create user notification preferences
     */
    public static function updateUserPreferences(int $userId, array $preferences): self
    {
        return static::updateOrCreate(
            ['user_id' => $userId],
            ['preferences' => $preferences]
        );
    }

    /**
     * Get user's HR notification preferences with defaults
     */
    public static function getUserHRPreferences(int $userId): array
    {
        $userPrefs = static::where('user_id', $userId)->first();
        $defaults = static::getDefaultHRPreferences();

        if (! $userPrefs) {
            return $defaults;
        }

        return array_merge_recursive($defaults, $userPrefs->preferences);
    }

    /**
     * Check if user has specific notification preference enabled
     */
    public static function isPreferenceEnabled(int $userId, string $module, string $category, string $type): bool
    {
        $preferences = static::getUserHRPreferences($userId);

        return $preferences[$module][$category][$type] ?? false;
    }
}
