<?php

namespace Modules\FileManagerCore\DTO;

use Illuminate\Http\UploadedFile;
use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Enums\FileVisibility;

class FileUploadRequest
{
    public function __construct(
        public readonly UploadedFile $file,
        public readonly FileType $type,
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly FileVisibility $visibility = FileVisibility::PRIVATE,
        public readonly ?string $attachableType = null,
        public readonly ?int $attachableId = null,
        public readonly array $metadata = [],
        public readonly ?int $categoryId = null,
        public readonly ?string $disk = null,
        public readonly ?int $userId = null
    ) {}

    /**
     * Create from HTTP request
     */
    public static function fromRequest(
        UploadedFile $file,
        FileType $type,
        ?string $attachableType = null,
        ?int $attachableId = null
    ): self {
        return new self(
            file: $file,
            type: $type,
            attachableType: $attachableType,
            attachableId: $attachableId
        );
    }

    /**
     * Set custom file name
     */
    public function withName(string $name): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $name,
            description: $this->description,
            visibility: $this->visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: $this->metadata,
            categoryId: $this->categoryId,
            disk: $this->disk,
            userId: $this->userId
        );
    }

    /**
     * Set description
     */
    public function withDescription(string $description): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $this->name,
            description: $description,
            visibility: $this->visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: $this->metadata,
            categoryId: $this->categoryId,
            disk: $this->disk,
            userId: $this->userId
        );
    }

    /**
     * Set visibility
     */
    public function withVisibility(FileVisibility $visibility): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            visibility: $visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: $this->metadata,
            categoryId: $this->categoryId,
            disk: $this->disk,
            userId: $this->userId
        );
    }

    /**
     * Set metadata
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            visibility: $this->visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: array_merge($this->metadata, $metadata),
            categoryId: $this->categoryId,
            disk: $this->disk,
            userId: $this->userId
        );
    }

    /**
     * Set category
     */
    public function withCategory(int $categoryId): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            visibility: $this->visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: $this->metadata,
            categoryId: $categoryId,
            disk: $this->disk,
            userId: $this->userId
        );
    }

    /**
     * Set storage disk
     */
    public function withDisk(string $disk): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            visibility: $this->visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: $this->metadata,
            categoryId: $this->categoryId,
            disk: $disk,
            userId: $this->userId
        );
    }

    /**
     * Set user ID
     */
    public function withUser(int $userId): self
    {
        return new self(
            file: $this->file,
            type: $this->type,
            name: $this->name,
            description: $this->description,
            visibility: $this->visibility,
            attachableType: $this->attachableType,
            attachableId: $this->attachableId,
            metadata: $this->metadata,
            categoryId: $this->categoryId,
            disk: $this->disk,
            userId: $userId
        );
    }

    /**
     * Get the final file name to use
     */
    public function getFileName(): string
    {
        if ($this->name) {
            $extension = $this->file->getClientOriginalExtension();

            return $this->name.($extension ? '.'.$extension : '');
        }

        return $this->file->getClientOriginalName();
    }

    /**
     * Get the storage path for this file
     */
    public function getStoragePath(): string
    {
        $directory = $this->type->directory();
        $fileName = $this->getFileName();

        // Add timestamp to prevent conflicts
        $timestamp = date('Y/m/d');

        return "{$directory}/{$timestamp}/{$fileName}";
    }

    /**
     * Validate the upload request
     */
    public function validate(): array
    {
        $errors = [];

        // Check file size
        $maxSize = $this->type->maxSize();
        if ($maxSize && $this->file->getSize() > ($maxSize * 1024)) {
            $errors[] = "File size exceeds maximum allowed size of {$maxSize}KB";
        }

        // Check MIME type if category has restrictions
        // This would be validated against category rules

        return $errors;
    }
}
