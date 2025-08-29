<?php

namespace Modules\FileManagerCore\DTO;

class StorageQuota
{
    public function __construct(
        public readonly int $usedSpace,
        public readonly ?int $totalSpace = null,
        public readonly int $fileCount = 0,
        public readonly string $provider = 'local'
    ) {}
    
    /**
     * Get available space in bytes
     */
    public function getAvailableSpace(): ?int
    {
        if ($this->totalSpace === null) {
            return null;
        }
        
        return max(0, $this->totalSpace - $this->usedSpace);
    }
    
    /**
     * Get usage percentage
     */
    public function getUsagePercentage(): ?float
    {
        if ($this->totalSpace === null || $this->totalSpace === 0) {
            return null;
        }
        
        return min(100, ($this->usedSpace / $this->totalSpace) * 100);
    }
    
    /**
     * Check if quota is exceeded
     */
    public function isExceeded(): bool
    {
        if ($this->totalSpace === null) {
            return false;
        }
        
        return $this->usedSpace > $this->totalSpace;
    }
    
    /**
     * Check if quota is near limit (above 80%)
     */
    public function isNearLimit(): bool
    {
        $percentage = $this->getUsagePercentage();
        
        return $percentage !== null && $percentage >= 80;
    }
    
    /**
     * Format used space for display
     */
    public function getFormattedUsedSpace(): string
    {
        return $this->formatBytes($this->usedSpace);
    }
    
    /**
     * Format total space for display
     */
    public function getFormattedTotalSpace(): string
    {
        if ($this->totalSpace === null) {
            return 'Unlimited';
        }
        
        return $this->formatBytes($this->totalSpace);
    }
    
    /**
     * Format available space for display
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
    
    /**
     * Check if can upload file of given size
     */
    public function canUpload(int $fileSize): bool
    {
        if ($this->totalSpace === null) {
            return true;
        }
        
        return ($this->usedSpace + $fileSize) <= $this->totalSpace;
    }
    
    /**
     * Get quota status for display
     */
    public function getStatus(): string
    {
        if ($this->isExceeded()) {
            return 'exceeded';
        }
        
        if ($this->isNearLimit()) {
            return 'warning';
        }
        
        return 'normal';
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'used_space' => $this->usedSpace,
            'total_space' => $this->totalSpace,
            'available_space' => $this->getAvailableSpace(),
            'file_count' => $this->fileCount,
            'provider' => $this->provider,
            'usage_percentage' => $this->getUsagePercentage(),
            'is_exceeded' => $this->isExceeded(),
            'is_near_limit' => $this->isNearLimit(),
            'status' => $this->getStatus(),
            'formatted' => [
                'used_space' => $this->getFormattedUsedSpace(),
                'total_space' => $this->getFormattedTotalSpace(),
                'available_space' => $this->getFormattedAvailableSpace()
            ]
        ];
    }
}