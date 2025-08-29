<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMenuPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'menu_slug',
        'is_pinned',
        'display_order',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the user that owns the menu preference.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
