<?php

namespace Modules\FileManagerCore\DTO;

use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Enums\FileStatus;
use Modules\FileManagerCore\Enums\FileVisibility;

class FileSearchRequest
{
    public function __construct(
        public readonly ?string $query = null,
        public readonly ?FileType $type = null,
        public readonly ?FileStatus $status = null,
        public readonly ?FileVisibility $visibility = null,
        public readonly ?int $categoryId = null,
        public readonly ?int $userId = null,
        public readonly ?string $mimeType = null,
        public readonly ?int $minSize = null,
        public readonly ?int $maxSize = null,
        public readonly ?string $dateFrom = null,
        public readonly ?string $dateTo = null,
        public readonly ?string $attachableType = null,
        public readonly ?int $attachableId = null,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc',
        public readonly int $perPage = 15,
        public readonly int $page = 1
    ) {}
    
    /**
     * Create from HTTP request parameters
     */
    public static function fromArray(array $data): self
    {
        return new self(
            query: $data['query'] ?? null,
            type: isset($data['type']) ? FileType::from($data['type']) : null,
            status: isset($data['status']) ? FileStatus::from($data['status']) : null,
            visibility: isset($data['visibility']) ? FileVisibility::from($data['visibility']) : null,
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            mimeType: $data['mime_type'] ?? null,
            minSize: isset($data['min_size']) ? (int) $data['min_size'] : null,
            maxSize: isset($data['max_size']) ? (int) $data['max_size'] : null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            attachableType: $data['attachable_type'] ?? null,
            attachableId: isset($data['attachable_id']) ? (int) $data['attachable_id'] : null,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortDirection: $data['sort_direction'] ?? 'desc',
            perPage: isset($data['per_page']) ? (int) $data['per_page'] : 15,
            page: isset($data['page']) ? (int) $data['page'] : 1
        );
    }
    
    /**
     * Check if search has any filters
     */
    public function hasFilters(): bool
    {
        return !empty($this->query) ||
               !is_null($this->type) ||
               !is_null($this->status) ||
               !is_null($this->visibility) ||
               !is_null($this->categoryId) ||
               !is_null($this->userId) ||
               !is_null($this->mimeType) ||
               !is_null($this->minSize) ||
               !is_null($this->maxSize) ||
               !is_null($this->dateFrom) ||
               !is_null($this->dateTo) ||
               !is_null($this->attachableType) ||
               !is_null($this->attachableId);
    }
    
    /**
     * Convert to array for query building
     */
    public function toArray(): array
    {
        return array_filter([
            'query' => $this->query,
            'type' => $this->type?->value,
            'status' => $this->status?->value,
            'visibility' => $this->visibility?->value,
            'category_id' => $this->categoryId,
            'user_id' => $this->userId,
            'mime_type' => $this->mimeType,
            'min_size' => $this->minSize,
            'max_size' => $this->maxSize,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'attachable_type' => $this->attachableType,
            'attachable_id' => $this->attachableId,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
            'page' => $this->page
        ], fn($value) => !is_null($value));
    }
}