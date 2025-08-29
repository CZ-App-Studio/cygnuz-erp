<?php

namespace Modules\FileManagerCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class FileShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_id',
        'shared_with_type',
        'shared_with_id',
        'permissions',
        'expires_at',
        'share_token',
        'download_count',
        'max_downloads',
        'created_by_id'
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'download_count' => 'integer',
        'max_downloads' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            if (empty($share->share_token)) {
                $share->share_token = Str::random(32);
            }
        });
    }

    /**
     * Get the file being shared
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    /**
     * Get the user who created the share
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    /**
     * Scope active shares (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope by share type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('shared_with_type', $type);
    }

    /**
     * Check if share is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if share has reached download limit
     */
    public function hasReachedDownloadLimit(): bool
    {
        return $this->max_downloads && $this->download_count >= $this->max_downloads;
    }

    /**
     * Check if share is still valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->hasReachedDownloadLimit();
    }

    /**
     * Check if permission is granted
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Get share URL
     */
    public function getShareUrl(): string
    {
        return route('filemanager.share.view', $this->share_token);
    }

    /**
     * Get time until expiration
     */
    public function getTimeUntilExpiration(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Get remaining downloads
     */
    public function getRemainingDownloads(): ?int
    {
        if (!$this->max_downloads) {
            return null; // Unlimited
        }

        return max(0, $this->max_downloads - $this->download_count);
    }

    /**
     * Get share status
     */
    public function getStatus(): string
    {
        if ($this->isExpired()) {
            return 'expired';
        }

        if ($this->hasReachedDownloadLimit()) {
            return 'limit_reached';
        }

        return 'active';
    }
}