<?php

namespace Modules\FileManagerCore\Contracts;

use Illuminate\Http\UploadedFile;
use Modules\FileManagerCore\DTO\FileUploadRequest;
use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Models\File;

interface FileManagerInterface
{
    /**
     * Upload a file
     */
    public function uploadFile(FileUploadRequest $request): File;

    /**
     * Upload multiple files
     */
    public function uploadFiles(array $files, FileType $type, ?string $attachableType = null, ?int $attachableId = null): array;

    /**
     * Get file by ID
     */
    public function getFile(int $id): ?File;

    /**
     * Get file by UUID
     */
    public function getFileByUuid(string $uuid): ?File;

    /**
     * Download file
     */
    public function downloadFile(File $file): \Symfony\Component\HttpFoundation\StreamedResponse;

    /**
     * Get file URL
     */
    public function getFileUrl(File $file): string;

    /**
     * Get temporary file URL
     */
    public function getTemporaryUrl(File $file, int $expirationMinutes = 60): string;

    /**
     * Delete file
     */
    public function deleteFile(File $file): bool;

    /**
     * Move file to different location
     */
    public function moveFile(File $file, string $newPath): bool;

    /**
     * Copy file
     */
    public function copyFile(File $file, string $newPath): File;

    /**
     * Update file metadata
     */
    public function updateFileMetadata(File $file, array $metadata): bool;

    /**
     * Generate file checksum
     */
    public function generateChecksum(File $file): string;

    /**
     * Verify file integrity
     */
    public function verifyFileIntegrity(File $file): bool;

    /**
     * Create file version
     */
    public function createVersion(File $file, UploadedFile $newFile): File;

    /**
     * Get file versions
     */
    public function getFileVersions(File $file): \Illuminate\Database\Eloquent\Collection;

    /**
     * Attach file to model
     */
    public function attachToModel(File $file, string $modelType, int $modelId): bool;

    /**
     * Detach file from model
     */
    public function detachFromModel(File $file): bool;
}
