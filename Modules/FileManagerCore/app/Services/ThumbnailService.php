<?php

namespace Modules\FileManagerCore\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Modules\FileManagerCore\Models\File;

class ThumbnailService
{
    protected ImageManager $imageManager;

    protected FileManagerSettingsService $settingsService;

    public function __construct(FileManagerSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->imageManager = new ImageManager(new Driver);
    }

    /**
     * Generate thumbnail for image file
     */
    public function generateThumbnail(File $file): ?string
    {
        if (! $this->settingsService->isThumbnailEnabled()) {
            return null;
        }

        if (! $this->canGenerateThumbnail($file)) {
            return null;
        }

        try {
            $config = $this->settingsService->getThumbnailConfig();
            $sourceDisk = Storage::disk($file->disk);
            $thumbnailDisk = Storage::disk($config['disk']);

            // Check if source file exists
            if (! $sourceDisk->exists($file->path)) {
                return null;
            }

            // Generate thumbnail path
            $thumbnailPath = $this->generateThumbnailPath($file);

            // Read original image
            $imageContent = $sourceDisk->get($file->path);
            $image = $this->imageManager->read($imageContent);

            // Resize image
            $image->scale(
                width: $config['max_width'],
                height: $config['max_height']
            );

            // Encode with quality setting
            $thumbnailContent = $image->toJpeg($config['quality']);

            // Save thumbnail
            $thumbnailDisk->put($thumbnailPath, $thumbnailContent);

            return $thumbnailPath;

        } catch (\Exception $e) {
            // Log error and return null
            \Log::error('Thumbnail generation failed: '.$e->getMessage(), [
                'file_id' => $file->id,
                'file_path' => $file->path,
            ]);

            return null;
        }
    }

    /**
     * Check if thumbnail can be generated for file
     */
    public function canGenerateThumbnail(File $file): bool
    {
        if (! $this->settingsService->isThumbnailEnabled()) {
            return false;
        }

        // Check if file is an image
        $imageTypes = $this->settingsService->getAllowedImageTypes();

        // Remove SVG from thumbnail generation as it's not supported by Intervention Image
        $supportedTypes = array_filter($imageTypes, function ($type) {
            return $type !== 'image/svg+xml';
        });

        return in_array($file->mime_type, $supportedTypes);
    }

    /**
     * Generate thumbnail path
     */
    protected function generateThumbnailPath(File $file): string
    {
        $pathInfo = pathinfo($file->path);
        $directory = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.'
            ? $pathInfo['dirname'].'/thumbnails/'
            : 'thumbnails/';

        $filename = $pathInfo['filename'].'_thumb.jpg';

        return $directory.$filename;
    }

    /**
     * Delete thumbnail for file
     */
    public function deleteThumbnail(File $file): bool
    {
        if (empty($file->thumbnail_path)) {
            return true;
        }

        try {
            $config = $this->settingsService->getThumbnailConfig();
            $thumbnailDisk = Storage::disk($config['disk']);

            if ($thumbnailDisk->exists($file->thumbnail_path)) {
                return $thumbnailDisk->delete($file->thumbnail_path);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Thumbnail deletion failed: '.$e->getMessage(), [
                'file_id' => $file->id,
                'thumbnail_path' => $file->thumbnail_path,
            ]);

            return false;
        }
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrl(File $file): ?string
    {
        if (empty($file->thumbnail_path)) {
            return null;
        }

        $config = $this->settingsService->getThumbnailConfig();
        $thumbnailDisk = Storage::disk($config['disk']);

        try {
            return $thumbnailDisk->url($file->thumbnail_path);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Regenerate thumbnail for file
     */
    public function regenerateThumbnail(File $file): ?string
    {
        // Delete existing thumbnail
        $this->deleteThumbnail($file);

        // Generate new thumbnail
        return $this->generateThumbnail($file);
    }

    /**
     * Batch generate thumbnails
     */
    public function batchGenerateThumbnails(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            if ($file instanceof File) {
                $thumbnailPath = $this->generateThumbnail($file);
                $results[$file->id] = [
                    'success' => ! is_null($thumbnailPath),
                    'thumbnail_path' => $thumbnailPath,
                ];

                // Update file record if thumbnail was generated
                if ($thumbnailPath) {
                    $file->update(['thumbnail_path' => $thumbnailPath]);
                }
            }
        }

        return $results;
    }

    /**
     * Clean up orphaned thumbnails
     */
    public function cleanupOrphanedThumbnails(): int
    {
        $config = $this->settingsService->getThumbnailConfig();
        $thumbnailDisk = Storage::disk($config['disk']);
        $cleaned = 0;

        try {
            // Get all thumbnail files
            $thumbnailFiles = $thumbnailDisk->allFiles('thumbnails');

            foreach ($thumbnailFiles as $thumbnailPath) {
                // Check if corresponding file record exists
                $fileExists = File::where('thumbnail_path', $thumbnailPath)->exists();

                if (! $fileExists) {
                    $thumbnailDisk->delete($thumbnailPath);
                    $cleaned++;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Thumbnail cleanup failed: '.$e->getMessage());
        }

        return $cleaned;
    }

    /**
     * Get thumbnail statistics
     */
    public function getThumbnailStats(): array
    {
        $totalFiles = File::whereNotNull('thumbnail_path')->count();
        $totalImages = File::whereIn('mime_type', $this->settingsService->getAllowedImageTypes())->count();

        return [
            'total_thumbnails' => $totalFiles,
            'total_images' => $totalImages,
            'thumbnail_coverage' => $totalImages > 0 ? round(($totalFiles / $totalImages) * 100, 2) : 0,
            'thumbnail_enabled' => $this->settingsService->isThumbnailEnabled(),
            'config' => $this->settingsService->getThumbnailConfig(),
        ];
    }
}
