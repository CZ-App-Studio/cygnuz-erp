<?php

namespace App\Helpers;

class VersionHelper
{
    /**
     * Get the application version
     */
    public static function getVersion(): string
    {
        return config('app.version', '0.0.0');
    }
    
    /**
     * Get version with codename
     */
    public static function getVersionWithCodename(): string
    {
        $version = config('app.version');
        $codename = config('app.version_codename');
        return $codename ? "{$version} ({$codename})" : $version;
    }
    
    /**
     * Get full version info
     */
    public static function getFullVersionInfo(): string
    {
        $version = config('app.version');
        $codename = config('app.version_codename');
        $date = config('app.version_date');
        return "{$version} - {$codename} ({$date})";
    }
    
    /**
     * Check if application is in alpha stage
     */
    public static function isAlpha(): bool
    {
        return config('app.version_stage') === 'alpha' || 
               str_contains(config('app.version'), 'alpha');
    }
    
    /**
     * Check if application is in beta stage
     */
    public static function isBeta(): bool
    {
        return config('app.version_stage') === 'beta' || 
               str_contains(config('app.version'), 'beta');
    }
    
    /**
     * Check if application is release candidate
     */
    public static function isRC(): bool
    {
        return config('app.version_stage') === 'rc' || 
               str_contains(config('app.version'), 'rc');
    }
    
    /**
     * Check if application is stable
     */
    public static function isStable(): bool
    {
        return config('app.version_stage') === 'stable' || 
               (!self::isAlpha() && !self::isBeta() && !self::isRC());
    }
    
    /**
     * Check if application is in development (not stable)
     */
    public static function isDevelopment(): bool
    {
        return !self::isStable();
    }
    
    /**
     * Get version stage badge HTML
     */
    public static function getVersionBadge(): string
    {
        $stage = config('app.version_stage', 'alpha');
        $colors = [
            'alpha' => 'danger',
            'beta' => 'warning',
            'rc' => 'info',
            'stable' => 'success'
        ];
        
        $color = $colors[$stage] ?? 'secondary';
        $label = ucfirst($stage);
        
        return "<span class='badge bg-{$color}'>{$label}</span>";
    }
    
    /**
     * Get version stage label
     */
    public static function getStageLabel(): string
    {
        $stage = config('app.version_stage', 'alpha');
        return ucfirst($stage);
    }
    
    /**
     * Get version stage color for Bootstrap
     */
    public static function getStageColor(): string
    {
        $stage = config('app.version_stage', 'alpha');
        $colors = [
            'alpha' => 'danger',
            'beta' => 'warning',
            'rc' => 'info',
            'stable' => 'success'
        ];
        
        return $colors[$stage] ?? 'secondary';
    }
    
    /**
     * Get warning message for non-stable versions
     */
    public static function getDevelopmentWarning(): ?string
    {
        if (self::isAlpha()) {
            return 'Alpha Version - Not for production use. Expect bugs and breaking changes.';
        }
        
        if (self::isBeta()) {
            return 'Beta Version - Testing phase. Use with caution in production.';
        }
        
        if (self::isRC()) {
            return 'Release Candidate - Final testing before stable release.';
        }
        
        return null;
    }
    
    /**
     * Get system information array
     */
    public static function getSystemInfo(): array
    {
        return [
            'app_version' => config('app.version'),
            'app_codename' => config('app.version_codename'),
            'app_stage' => config('app.version_stage'),
            'release_date' => config('app.version_date'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'debug_mode' => config('app.debug') ? 'Enabled' : 'Disabled',
            'environment' => config('app.env'),
        ];
    }
    
    /**
     * Compare versions
     */
    public static function isNewerVersion(string $version1, string $version2): bool
    {
        // Remove stage suffixes for comparison
        $v1 = explode('-', $version1)[0];
        $v2 = explode('-', $version2)[0];
        
        return version_compare($v1, $v2, '>');
    }
    
    /**
     * Get update availability message
     */
    public static function checkForUpdates(string $latestVersion): ?string
    {
        $currentVersion = config('app.version');
        
        if (self::isNewerVersion($latestVersion, $currentVersion)) {
            return "Update available: {$latestVersion}";
        }
        
        return null;
    }
}