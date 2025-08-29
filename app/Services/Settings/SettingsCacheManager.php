<?php

namespace App\Services\Settings;

use Closure;
use Illuminate\Support\Facades\Cache;

class SettingsCacheManager
{
    /**
     * Cache tags for settings
     */
    protected array $tags = ['settings'];

    /**
     * Cache TTL in seconds
     */
    protected int $ttl = 3600;

    /**
     * Remember a value in cache
     */
    public function remember(string $key, Closure $callback, ?int $ttl = null)
    {
        $ttl = $ttl ?? $this->ttl;
        
        if ($this->supportsTags()) {
            return Cache::tags($this->tags)->remember($key, $ttl, $callback);
        }
        
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Forget a cached value
     */
    public function forget(string $key): bool
    {
        if ($this->supportsTags()) {
            return Cache::tags($this->tags)->forget($key);
        }
        
        return Cache::forget($key);
    }

    /**
     * Flush all cached settings
     */
    public function flush(): bool
    {
        if ($this->supportsTags()) {
            return Cache::tags($this->tags)->flush();
        }
        
        // If tags not supported, manually clear known keys
        $keys = [
            'system_settings',
            'module_settings_all',
            'global_settings',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
        
        // Clear module-specific caches
        $this->clearModuleCaches();
        
        return true;
    }

    /**
     * Set cache tags
     */
    public function tags(array $tags): self
    {
        $this->tags = array_merge(['settings'], $tags);
        return $this;
    }

    /**
     * Set cache TTL
     */
    public function ttl(int $seconds): self
    {
        $this->ttl = $seconds;
        return $this;
    }

    /**
     * Get a value from cache
     */
    public function get(string $key, $default = null)
    {
        if ($this->supportsTags()) {
            return Cache::tags($this->tags)->get($key, $default);
        }
        
        return Cache::get($key, $default);
    }

    /**
     * Put a value in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->ttl;
        
        if ($this->supportsTags()) {
            return Cache::tags($this->tags)->put($key, $value, $ttl);
        }
        
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Check if cache driver supports tags
     */
    protected function supportsTags(): bool
    {
        $driver = Cache::getDefaultDriver();
        return in_array($driver, ['redis', 'memcached', 'dynamodb', 'apc']);
    }

    /**
     * Clear module-specific caches
     */
    protected function clearModuleCaches(): void
    {
        // This would need to track active modules
        // For now, we'll clear common module patterns
        $patterns = [
            'module_settings_*',
            'module_config_*',
        ];
        
        if (Cache::getDefaultDriver() === 'redis') {
            foreach ($patterns as $pattern) {
                $keys = Cache::connection()->keys($pattern);
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        }
    }

    /**
     * Warm up cache
     */
    public function warmUp(): void
    {
        // Load system settings
        app(SettingsService::class)->refresh();
        
        // Load module settings
        app(ModuleSettingsService::class)->getAllGrouped();
    }
}