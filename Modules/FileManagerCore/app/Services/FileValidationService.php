<?php

namespace Modules\FileManagerCore\Services;

use Illuminate\Http\UploadedFile;
use Modules\FileManagerCore\Services\FileManagerSettingsService;

class FileValidationService
{
    protected FileManagerSettingsService $settingsService;

    public function __construct(FileManagerSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Validate uploaded file
     */
    public function validateFile(UploadedFile $file): array
    {
        $errors = [];

        // Check file size
        if (!$this->validateFileSize($file)) {
            $maxSizeMB = round($this->settingsService->getMaxFileSize() / 1024 / 1024, 2);
            $errors[] = "File size exceeds the maximum allowed size of {$maxSizeMB}MB";
        }

        // Check MIME type
        if (!$this->validateMimeType($file)) {
            $allowedTypes = implode(', ', $this->settingsService->getAllowedMimeTypes());
            $errors[] = "File type '{$file->getMimeType()}' is not allowed. Allowed types: {$allowedTypes}";
        }

        // Additional security checks
        if (!$this->validateFileName($file)) {
            $errors[] = "File name contains invalid characters";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate file size
     */
    public function validateFileSize(UploadedFile $file): bool
    {
        $maxSize = $this->settingsService->getMaxFileSize();
        return $file->getSize() <= $maxSize;
    }

    /**
     * Validate MIME type
     */
    public function validateMimeType(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return $this->settingsService->isMimeTypeAllowed($mimeType);
    }

    /**
     * Validate file name for security
     */
    public function validateFileName(UploadedFile $file): bool
    {
        $fileName = $file->getClientOriginalName();
        
        // Check for dangerous file names
        $dangerousPatterns = [
            '/\.\./','/__/','/\\\\/' ,'/<\?php/','/\x00/',
            '/\.(bat|cmd|com|cpl|dll|exe|scr|pif)$/i'
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $fileName)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if file is an image
     */
    public function isImage(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, $this->settingsService->getAllowedImageTypes());
    }

    /**
     * Check if file is a document
     */
    public function isDocument(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return in_array($mimeType, $this->settingsService->getAllowedDocumentTypes());
    }

    /**
     * Get file type category
     */
    public function getFileCategory(UploadedFile $file): string
    {
        if ($this->isImage($file)) {
            return 'image';
        }
        
        if ($this->isDocument($file)) {
            return 'document';
        }
        
        return 'other';
    }

    /**
     * Validate multiple files
     */
    public function validateFiles(array $files): array
    {
        $results = [];
        
        foreach ($files as $index => $file) {
            if ($file instanceof UploadedFile) {
                $results[$index] = $this->validateFile($file);
            } else {
                $results[$index] = [
                    'valid' => false,
                    'errors' => ['Invalid file upload']
                ];
            }
        }
        
        return $results;
    }

    /**
     * Check storage quota for user
     */
    public function checkUserQuota(int $userId, int $fileSize): bool
    {
        // Get current usage
        $currentUsage = $this->getCurrentUserUsage($userId);
        $maxQuota = $this->settingsService->getUserQuota();
        
        if ($maxQuota === 0) {
            return true; // Unlimited quota
        }
        
        return ($currentUsage + $fileSize) <= $maxQuota;
    }

    /**
     * Check storage quota for department
     */
    public function checkDepartmentQuota(int $departmentId, int $fileSize): bool
    {
        // Get current usage
        $currentUsage = $this->getCurrentDepartmentUsage($departmentId);
        $maxQuota = $this->settingsService->getDepartmentQuota();
        
        if ($maxQuota === 0) {
            return true; // Unlimited quota
        }
        
        return ($currentUsage + $fileSize) <= $maxQuota;
    }

    /**
     * Get current user storage usage
     */
    protected function getCurrentUserUsage(int $userId): int
    {
        // This would typically query the database
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get current department storage usage
     */
    protected function getCurrentDepartmentUsage(int $departmentId): int
    {
        // This would typically query the database
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get validation rules for file upload
     */
    public function getValidationRules(): array
    {
        $maxSizeKB = $this->settingsService->getMaxFileSize() / 1024;
        $allowedMimes = implode(',', $this->settingsService->getAllowedMimeTypes());
        
        return [
            'required',
            'file',
            "max:{$maxSizeKB}",
            "mimes:{$allowedMimes}"
        ];
    }

    /**
     * Get validation messages
     */
    public function getValidationMessages(): array
    {
        $maxSizeMB = round($this->settingsService->getMaxFileSize() / 1024 / 1024, 2);
        
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.max' => "File size must not exceed {$maxSizeMB}MB.",
            'file.mimes' => 'File type is not allowed.',
        ];
    }
}