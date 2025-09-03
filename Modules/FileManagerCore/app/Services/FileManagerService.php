<?php

namespace Modules\FileManagerCore\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\FileManagerCore\Contracts\FileManagerInterface;
use Modules\FileManagerCore\DTO\FileUploadRequest;
use Modules\FileManagerCore\Enums\FileStatus;
use Modules\FileManagerCore\Models\File;

class FileManagerService implements FileManagerInterface
{
    protected StorageDriverManager $storageManager;

    protected FileValidationService $validationService;

    protected FileManagerSettingsService $settingsService;

    public function __construct(
        StorageDriverManager $storageManager,
        FileValidationService $validationService,
        FileManagerSettingsService $settingsService
    ) {
        $this->storageManager = $storageManager;
        $this->validationService = $validationService;
        $this->settingsService = $settingsService;
    }

    /**
     * Upload a file
     */
    public function uploadFile(FileUploadRequest $request): File
    {
        // Validate the file
        $validation = $this->validationService->validateFile($request->file);
        if (! $validation['valid']) {
            throw new \InvalidArgumentException('File validation failed: '.implode(', ', $validation['errors']));
        }

        // Validate user quota if user is specified
        if ($request->userId) {
            if (! $this->validationService->checkUserQuota($request->userId, $request->file->getSize())) {
                throw new \InvalidArgumentException('User storage quota exceeded. Cannot upload file.');
            }
        }

        // Validate department quota if user has department
        if ($request->userId && Auth::check()) {
            $user = Auth::user();
            if (method_exists($user, 'department_id') && $user->department_id) {
                if (! $this->validationService->checkDepartmentQuota($user->department_id, $request->file->getSize())) {
                    throw new \InvalidArgumentException('Department storage quota exceeded. Cannot upload file.');
                }
            }
        }

        // Generate unique filename
        $originalName = $request->file->getClientOriginalName();
        $extension = $request->file->getClientOriginalExtension();
        $fileName = $request->name ?? pathinfo($originalName, PATHINFO_FILENAME);
        $uniqueFileName = "{$fileName}_".time().".{$extension}";

        // Determine storage path
        $directory = $request->type->directory();

        // Store the file
        $disk = $request->disk ?? $this->settingsService->getDefaultDisk();
        $path = Storage::disk($disk)->putFileAs($directory, $request->file, $uniqueFileName);

        // Create file record
        $file = File::create([
            'uuid' => Str::uuid(),
            'name' => $request->name ?? $fileName,
            'original_name' => $originalName,
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $request->file->getMimeType(),
            'size' => $request->file->getSize(),
            'status' => FileStatus::ACTIVE,
            'visibility' => $request->visibility,
            'description' => $request->description,
            'metadata' => array_merge($request->metadata, [
                'type' => $request->type->value,
                'filename' => $uniqueFileName, // Store filename in metadata instead
                'upload_ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]),
            'attachable_type' => $request->attachableType,
            'attachable_id' => $request->attachableId,
            'category_id' => $request->categoryId,
            'created_by_id' => $request->userId ?? (Auth::check() ? Auth::id() : null),
        ]);

        return $file;
    }

    /**
     * Upload multiple files
     */
    public function uploadFiles(array $files, \Modules\FileManagerCore\Enums\FileType $type, ?string $attachableType = null, ?int $attachableId = null): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            $uploadRequest = FileUploadRequest::fromRequest($file, $type, $attachableType, $attachableId);
            $uploadedFiles[] = $this->uploadFile($uploadRequest);
        }

        return $uploadedFiles;
    }

    /**
     * Get file by ID
     */
    public function getFile(int $id): ?File
    {
        return File::find($id);
    }

    /**
     * Get file by UUID
     */
    public function getFileByUuid(string $uuid): ?File
    {
        return File::where('uuid', $uuid)->first();
    }

    /**
     * Download file
     */
    public function downloadFile(File $file): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $disk = Storage::disk($file->disk);

        if (method_exists($disk, 'download')) {
            return $disk->download($file->path, $file->original_name);
        }

        // Fallback for disks that don't support download method
        $headers = [
            'Content-Type' => $file->mime_type,
            'Content-Disposition' => "attachment; filename=\"{$file->original_name}\"",
        ];

        return response()->stream(function () use ($disk, $file) {
            echo $disk->get($file->path);
        }, 200, $headers);
    }

    /**
     * Get file URL
     */
    public function getFileUrl(File $file): string
    {
        $disk = Storage::disk($file->disk);

        if (method_exists($disk, 'url')) {
            return $disk->url($file->path);
        }

        // Fallback for disks that don't support URL method
        return asset("storage/{$file->path}");
    }

    /**
     * Get temporary file URL
     */
    public function getTemporaryUrl(File $file, int $expirationMinutes = 60): string
    {
        $disk = Storage::disk($file->disk);

        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($file->path, now()->addMinutes($expirationMinutes));
        }

        // Fallback: return regular URL (not temporary)
        return $this->getFileUrl($file);
    }

    /**
     * Delete file
     */
    public function deleteFile(File $file): bool
    {
        // Delete physical file
        Storage::disk($file->disk)->delete($file->path);

        // Update file status
        $file->update(['status' => FileStatus::DELETED]);

        return true;
    }

    /**
     * Move file to different location
     */
    public function moveFile(File $file, string $newPath): bool
    {
        $oldPath = $file->path;

        if (Storage::disk($file->disk)->move($oldPath, $newPath)) {
            $file->update(['path' => $newPath]);

            return true;
        }

        return false;
    }

    /**
     * Copy file
     */
    public function copyFile(File $file, string $newPath): File
    {
        Storage::disk($file->disk)->copy($file->path, $newPath);

        $newFile = $file->replicate();
        $newFile->uuid = Str::uuid();
        $newFile->path = $newPath;
        $newFile->save();

        return $newFile;
    }

    /**
     * Update file metadata
     */
    public function updateFileMetadata(File $file, array $metadata): bool
    {
        return $file->update(['metadata' => array_merge($file->metadata ?? [], $metadata)]);
    }

    /**
     * Generate file checksum
     */
    public function generateChecksum(File $file): string
    {
        $content = Storage::disk($file->disk)->get($file->path);

        return md5($content);
    }

    /**
     * Verify file integrity
     */
    public function verifyFileIntegrity(File $file): bool
    {
        return Storage::disk($file->disk)->exists($file->path);
    }

    /**
     * Create file version
     */
    public function createVersion(File $file, UploadedFile $newFile): File
    {
        // Implementation for file versioning
        throw new \Exception('File versioning not implemented yet');
    }

    /**
     * Get file versions
     */
    public function getFileVersions(File $file): \Illuminate\Database\Eloquent\Collection
    {
        // Implementation for getting file versions
        return collect();
    }

    /**
     * Attach file to model
     */
    public function attachToModel(File $file, string $modelType, int $modelId): bool
    {
        return $file->update([
            'attachable_type' => $modelType,
            'attachable_id' => $modelId,
        ]);
    }

    /**
     * Detach file from model
     */
    public function detachFromModel(File $file): bool
    {
        return $file->update([
            'attachable_type' => null,
            'attachable_id' => null,
        ]);
    }
}
