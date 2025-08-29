<?php

namespace Modules\FileManagerCore\Contracts;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;

interface StorageDriverInterface
{
    /**
     * Store a file and return storage information
     */
    public function store(UploadedFile $file, string $path, string $visibility = 'private'): array;
    
    /**
     * Get file contents
     */
    public function get(string $path): ?string;
    
    /**
     * Check if file exists
     */
    public function exists(string $path): bool;
    
    /**
     * Delete a file
     */
    public function delete(string $path): bool;
    
    /**
     * Move a file from one location to another
     */
    public function move(string $from, string $to): bool;
    
    /**
     * Copy a file from one location to another
     */
    public function copy(string $from, string $to): bool;
    
    /**
     * Get public URL for a file
     */
    public function url(string $path): string;
    
    /**
     * Get temporary URL for a file
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration): string;
    
    /**
     * Get file metadata
     */
    public function getMetadata(string $path): array;
    
    /**
     * Get file size in bytes
     */
    public function getSize(string $path): int;
    
    /**
     * Get last modified timestamp
     */
    public function getLastModified(string $path): int;
    
    /**
     * Set file visibility
     */
    public function setVisibility(string $path, string $visibility): bool;
    
    /**
     * Get file visibility
     */
    public function getVisibility(string $path): string;
    
    /**
     * Check if driver is healthy and accessible
     */
    public function healthCheck(): array;
    
    /**
     * Get driver configuration
     */
    public function getConfig(): array;
    
    /**
     * Validate driver configuration
     */
    public function validateConfig(array $config): bool;
}