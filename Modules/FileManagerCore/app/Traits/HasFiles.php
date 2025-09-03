<?php

namespace Modules\FileManagerCore\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\FileManagerCore\Enums\FileStatus;
use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Models\File;

trait HasFiles
{
    /**
     * Get all files attached to this model
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'attachable')
            ->where('status', FileStatus::ACTIVE);
    }

    /**
     * Get all files including inactive ones
     */
    public function allFiles(): MorphMany
    {
        return $this->morphMany(File::class, 'attachable');
    }

    /**
     * Get files by type
     */
    public function filesByType(FileType $type): Collection
    {
        return $this->files()
            ->where('metadata->type', $type->value)
            ->get();
    }

    /**
     * Get single file by type (useful for profile pictures, etc.)
     */
    public function fileByType(FileType $type): ?File
    {
        return $this->files()
            ->where('metadata->type', $type->value)
            ->first();
    }

    /**
     * Get images only
     */
    public function images(): Collection
    {
        return $this->files()
            ->where('mime_type', 'LIKE', 'image/%')
            ->get();
    }

    /**
     * Get documents only
     */
    public function documents(): Collection
    {
        $documentMimes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
        ];

        return $this->files()
            ->whereIn('mime_type', $documentMimes)
            ->get();
    }

    /**
     * Attach a file to this model
     */
    public function attachFile(File $file): bool
    {
        $file->update([
            'attachable_type' => static::class,
            'attachable_id' => $this->id,
        ]);

        return true;
    }

    /**
     * Detach a file from this model
     */
    public function detachFile(File $file): bool
    {
        if ($file->attachable_type === static::class && $file->attachable_id === $this->id) {
            $file->update([
                'attachable_type' => null,
                'attachable_id' => null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Detach all files from this model
     */
    public function detachAllFiles(): int
    {
        return $this->files()->update([
            'attachable_type' => null,
            'attachable_id' => null,
        ]);
    }

    /**
     * Get total file count
     */
    public function getFileCount(): int
    {
        return $this->files()->count();
    }

    /**
     * Get total file size in bytes
     */
    public function getTotalFileSize(): int
    {
        return $this->files()->sum('size');
    }

    /**
     * Get formatted total file size
     */
    public function getFormattedTotalFileSize(): string
    {
        return $this->formatBytes($this->getTotalFileSize());
    }

    /**
     * Check if model has files
     */
    public function hasFiles(): bool
    {
        return $this->files()->exists();
    }

    /**
     * Check if model has file of specific type
     */
    public function hasFileOfType(FileType $type): bool
    {
        return $this->files()
            ->where('metadata->type', $type->value)
            ->exists();
    }

    /**
     * Get first image file
     */
    public function getFirstImage(): ?File
    {
        return $this->files()
            ->where('mime_type', 'LIKE', 'image/%')
            ->first();
    }

    /**
     * Get profile picture (shorthand for employee profile picture)
     */
    public function getProfilePicture(): ?File
    {
        return $this->fileByType(FileType::EMPLOYEE_PROFILE_PICTURE);
    }

    /**
     * Get profile picture URL
     */
    public function getProfilePictureUrl(): ?string
    {
        $profilePicture = $this->getProfilePicture();

        if (! $profilePicture) {
            return null;
        }

        // This would use the FileManagerService to get the URL
        return app(\Modules\FileManagerCore\Contracts\FileManagerInterface::class)
            ->getFileUrl($profilePicture);
    }

    /**
     * Boot the trait
     */
    protected static function bootHasFiles(): void
    {
        // When model is deleted, optionally handle file cleanup
        static::deleting(function ($model) {
            if (method_exists($model, 'shouldDeleteFilesOnModelDelete') &&
                $model->shouldDeleteFilesOnModelDelete()) {

                $model->files()->each(function ($file) {
                    app(\Modules\FileManagerCore\Contracts\FileManagerInterface::class)
                        ->deleteFile($file);
                });
            } else {
                // Just detach files
                $model->detachAllFiles();
            }
        });
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

        return round($bytes, 2).' '.$units[$i];
    }
}
