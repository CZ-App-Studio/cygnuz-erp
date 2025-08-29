<?php

namespace Modules\FileManagerCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StorageUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'storage_provider',
        'used_space',
        'file_count',
        'quota_limit',
        'last_calculated_at'
    ];

    protected $casts = [
        'used_space' => 'integer',
        'file_count' => 'integer',
        'quota_limit' => 'integer',
        'last_calculated_at' => 'datetime'
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope by user
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope by department
     */
    public function scopeForDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Scope by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('storage_provider', $provider);
    }

    /**
     * Add to used space
     */
    public function addUsage(int $size): void
    {
        $this->increment('used_space', $size);
        $this->increment('file_count');
        $this->update(['last_calculated_at' => now()]);
    }

    /**
     * Remove from used space
     */
    public function removeUsage(int $size): void
    {
        $this->decrement('used_space', max(0, $size));
        $this->decrement('file_count');
        $this->update(['last_calculated_at' => now()]);
    }

    /**
     * Check if quota is exceeded
     */
    public function isQuotaExceeded(): bool
    {
        if (!$this->quota_limit) {
            return false;
        }

        return $this->used_space > $this->quota_limit;
    }

    /**
     * Check if quota is near limit (above 80%)
     */
    public function isNearQuotaLimit(): bool
    {
        if (!$this->quota_limit) {
            return false;
        }

        return ($this->used_space / $this->quota_limit) >= 0.8;
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): ?float
    {
        if (!$this->quota_limit || $this->quota_limit === 0) {
            return null;
        }

        return min(100, ($this->used_space / $this->quota_limit) * 100);
    }

    /**
     * Get available space
     */
    public function getAvailableSpace(): ?int
    {
        if (!$this->quota_limit) {
            return null; // Unlimited
        }

        return max(0, $this->quota_limit - $this->used_space);
    }

    /**
     * Check if can upload file of given size
     */
    public function canUpload(int $fileSize): bool
    {
        if (!$this->quota_limit) {
            return true; // No quota limit
        }

        return ($this->used_space + $fileSize) <= $this->quota_limit;
    }

    /**
     * Get formatted used space
     */
    public function getFormattedUsedSpace(): string
    {
        return $this->formatBytes($this->used_space);
    }

    /**
     * Get formatted quota limit
     */
    public function getFormattedQuotaLimit(): string
    {
        if (!$this->quota_limit) {
            return 'Unlimited';
        }

        return $this->formatBytes($this->quota_limit);
    }

    /**
     * Get formatted available space
     */
    public function getFormattedAvailableSpace(): string
    {
        $available = $this->getAvailableSpace();
        
        if ($available === null) {
            return 'Unlimited';
        }

        return $this->formatBytes($available);
    }

    /**
     * Recalculate usage from actual files
     */
    public function recalculate(): void
    {
        $stats = File::where(function ($query) {
                if ($this->user_id) {
                    $query->where('created_by_id', $this->user_id);
                }
                // Add department logic here when available
            })
            ->where('storage_provider', $this->storage_provider)
            ->where('status', 'active')
            ->selectRaw('SUM(size) as total_size, COUNT(*) as total_count')
            ->first();

        $this->update([
            'used_space' => $stats->total_size ?? 0,
            'file_count' => $stats->total_count ?? 0,
            'last_calculated_at' => now()
        ]);
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}