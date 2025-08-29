<?php

namespace Modules\FileManagerCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\FileManagerCore\Enums\FileStatus;
use Modules\FileManagerCore\Enums\FileVisibility;
use Illuminate\Support\Str;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
        'category_id',
        'description',
        'metadata',
        'visibility',
        'thumbnail_path',
        'download_count',
        'last_accessed_at',
        'checksum',
        'storage_provider',
        'attachable_type',
        'attachable_id',
        'status',
        'version',
        'parent_file_id',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'size' => 'integer',
        'download_count' => 'integer',
        'last_accessed_at' => 'datetime',
        'metadata' => 'array',
        'visibility' => FileVisibility::class,
        'status' => FileStatus::class,
        'version' => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($file) {
            if (empty($file->uuid)) {
                $file->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the attachable model (polymorphic relation)
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the file category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FileCategory::class, 'category_id');
    }

    /**
     * Get the user who created the file
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    /**
     * Get the user who last updated the file
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by_id');
    }

    /**
     * Get the parent file (for versions)
     */
    public function parentFile(): BelongsTo
    {
        return $this->belongsTo(File::class, 'parent_file_id');
    }

    /**
     * Get file versions
     */
    public function versions(): HasMany
    {
        return $this->hasMany(File::class, 'parent_file_id')->orderBy('version', 'desc');
    }

    /**
     * Get file shares
     */
    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    /**
     * Get file version history
     */
    public function versionHistory(): HasMany
    {
        return $this->hasMany(FileVersion::class);
    }

    /**
     * Scope active files
     */
    public function scopeActive($query)
    {
        return $query->where('status', FileStatus::ACTIVE);
    }

    /**
     * Scope by visibility
     */
    public function scopeByVisibility($query, FileVisibility $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope by mime type
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * Scope by size range
     */
    public function scopeBySizeRange($query, ?int $minSize = null, ?int $maxSize = null)
    {
        if ($minSize !== null) {
            $query->where('size', '>=', $minSize);
        }
        
        if ($maxSize !== null) {
            $query->where('size', '<=', $maxSize);
        }
        
        return $query;
    }

    /**
     * Scope search by name
     */
    public function scopeSearch($query, ?string $search = null)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('original_name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Check if file is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if file is a video
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * Check if file is an audio
     */
    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    /**
     * Check if file is a document
     */
    public function isDocument(): bool
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'
        ];

        return in_array($this->mime_type, $documentMimes);
    }

    /**
     * Get human-readable file size
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->size);
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Get file icon based on type
     */
    public function getIconAttribute(): string
    {
        if ($this->isImage()) {
            return 'fas fa-image';
        }
        
        if ($this->isVideo()) {
            return 'fas fa-video';
        }
        
        if ($this->isAudio()) {
            return 'fas fa-music';
        }
        
        if ($this->isDocument()) {
            return 'fas fa-file-alt';
        }
        
        return 'fas fa-file';
    }

    /**
     * Increment download count
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
        $this->update(['last_accessed_at' => now()]);
    }

    /**
     * Check if file can be previewed
     */
    public function canPreview(): bool
    {
        return $this->isImage() || $this->mime_type === 'application/pdf';
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
     * Get the route key name for the model
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}