<?php

namespace Modules\FileManagerCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'parent_id',
        'is_active',
        'sort_order',
        'max_file_size',
        'allowed_mime_types'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'max_file_size' => 'integer',
        'allowed_mime_types' => 'array'
    ];

    /**
     * Get the parent category
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(FileCategory::class, 'parent_id');
    }

    /**
     * Get child categories
     */
    public function children(): HasMany
    {
        return $this->hasMany(FileCategory::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Get files in this category
     */
    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'category_id');
    }

    /**
     * Scope active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get category hierarchy as breadcrumb
     */
    public function getBreadcrumb(): array
    {
        $breadcrumb = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumb, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug
            ]);
            $category = $category->parent;
        }

        return $breadcrumb;
    }

    /**
     * Check if MIME type is allowed
     */
    public function isMimeTypeAllowed(string $mimeType): bool
    {
        if (empty($this->allowed_mime_types)) {
            return true; // No restrictions
        }

        return in_array($mimeType, $this->allowed_mime_types);
    }

    /**
     * Check if file size is within limit
     */
    public function isFileSizeAllowed(int $size): bool
    {
        if (!$this->max_file_size) {
            return true; // No size limit
        }

        return $size <= $this->max_file_size;
    }

    /**
     * Get human-readable max file size
     */
    public function getFormattedMaxSizeAttribute(): ?string
    {
        if (!$this->max_file_size) {
            return null;
        }

        return $this->formatBytes($this->max_file_size);
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}