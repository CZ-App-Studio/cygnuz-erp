<?php

namespace Modules\FileManagerCore\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\FileManagerCore\DTO\StorageQuota;
use Modules\FileManagerCore\Models\StorageUsage;

trait TracksStorage
{
    /**
     * Get storage usage records
     */
    public function storageUsages(): HasMany
    {
        return $this->hasMany(StorageUsage::class, 'user_id');
    }

    /**
     * Get storage usage for specific provider
     */
    public function getStorageUsage(string $provider = 'local'): ?StorageUsage
    {
        return $this->storageUsages()
            ->where('storage_provider', $provider)
            ->first();
    }

    /**
     * Get or create storage usage record
     */
    public function getOrCreateStorageUsage(string $provider = 'local'): StorageUsage
    {
        return $this->storageUsages()
            ->firstOrCreate(
                ['storage_provider' => $provider],
                [
                    'used_space' => 0,
                    'file_count' => 0,
                    'quota_limit' => $this->getDefaultQuotaLimit(),
                    'last_calculated_at' => now(),
                ]
            );
    }

    /**
     * Get storage quota for provider
     */
    public function getStorageQuota(string $provider = 'local'): StorageQuota
    {
        $usage = $this->getStorageUsage($provider);

        if (! $usage) {
            return new StorageQuota(
                usedSpace: 0,
                totalSpace: $this->getDefaultQuotaLimit(),
                fileCount: 0,
                provider: $provider
            );
        }

        return new StorageQuota(
            usedSpace: $usage->used_space,
            totalSpace: $usage->quota_limit,
            fileCount: $usage->file_count,
            provider: $provider
        );
    }

    /**
     * Get total storage usage across all providers
     */
    public function getTotalStorageUsage(): array
    {
        $usages = $this->storageUsages;
        $total = [
            'used_space' => 0,
            'file_count' => 0,
            'providers' => [],
        ];

        foreach ($usages as $usage) {
            $quota = new StorageQuota(
                usedSpace: $usage->used_space,
                totalSpace: $usage->quota_limit,
                fileCount: $usage->file_count,
                provider: $usage->storage_provider
            );

            $total['used_space'] += $usage->used_space;
            $total['file_count'] += $usage->file_count;
            $total['providers'][$usage->storage_provider] = $quota->toArray();
        }

        return $total;
    }

    /**
     * Add storage usage
     */
    public function addStorageUsage(int $size, string $provider = 'local'): void
    {
        $usage = $this->getOrCreateStorageUsage($provider);
        $usage->addUsage($size);
    }

    /**
     * Remove storage usage
     */
    public function removeStorageUsage(int $size, string $provider = 'local'): void
    {
        $usage = $this->getStorageUsage($provider);

        if ($usage) {
            $usage->removeUsage($size);
        }
    }

    /**
     * Check if can upload file of given size
     */
    public function canUploadFile(int $fileSize, string $provider = 'local'): bool
    {
        $quota = $this->getStorageQuota($provider);

        return $quota->canUpload($fileSize);
    }

    /**
     * Check if storage quota is exceeded
     */
    public function isStorageQuotaExceeded(string $provider = 'local'): bool
    {
        $quota = $this->getStorageQuota($provider);

        return $quota->isExceeded();
    }

    /**
     * Check if storage is near quota limit
     */
    public function isStorageNearLimit(string $provider = 'local'): bool
    {
        $quota = $this->getStorageQuota($provider);

        return $quota->isNearLimit();
    }

    /**
     * Get storage usage percentage
     */
    public function getStorageUsagePercentage(string $provider = 'local'): ?float
    {
        $quota = $this->getStorageQuota($provider);

        return $quota->getUsagePercentage();
    }

    /**
     * Recalculate storage usage from actual files
     */
    public function recalculateStorageUsage(string $provider = 'local'): void
    {
        $usage = $this->getOrCreateStorageUsage($provider);
        $usage->recalculate();
    }

    /**
     * Recalculate all storage usages
     */
    public function recalculateAllStorageUsages(): void
    {
        foreach ($this->storageUsages as $usage) {
            $usage->recalculate();
        }
    }

    /**
     * Get formatted storage usage
     */
    public function getFormattedStorageUsage(string $provider = 'local'): array
    {
        $quota = $this->getStorageQuota($provider);

        return $quota->toArray();
    }

    /**
     * Set storage quota limit
     */
    public function setStorageQuotaLimit(int $limit, string $provider = 'local'): void
    {
        $usage = $this->getOrCreateStorageUsage($provider);
        $usage->update(['quota_limit' => $limit]);
    }

    /**
     * Get default quota limit from config
     */
    protected function getDefaultQuotaLimit(): ?int
    {
        $quotaConfig = config('filemanagercore.quotas.per_user');

        return $quotaConfig ?: null;
    }

    /**
     * Boot the trait
     */
    protected static function bootTracksStorage(): void
    {
        // When user is deleted, clean up storage usage records
        static::deleting(function ($model) {
            $model->storageUsages()->delete();
        });
    }
}
