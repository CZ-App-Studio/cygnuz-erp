<?php

namespace Modules\FileManagerCore\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\FileManagerCore\Contracts\FileManagerInterface;
use Modules\FileManagerCore\Models\File;
use Modules\FileManagerCore\Services\FileSecurityService;

class FileController extends Controller
{
    protected FileManagerInterface $fileManager;

    protected FileSecurityService $securityService;

    public function __construct(
        FileManagerInterface $fileManager,
        FileSecurityService $securityService
    ) {
        $this->fileManager = $fileManager;
        $this->securityService = $securityService;
    }

    /**
     * Download a file by UUID
     */
    public function download(string $uuid)
    {
        $file = File::where('uuid', $uuid)->firstOrFail();

        // Check if user has permission to download
        // if (! $this->securityService->canDownload($file)) {
        //     abort(403, 'You do not have permission to download this file.');
        // }

        // For public disk files, redirect to the storage URL
        if ($file->disk === 'public') {
            return redirect(Storage::disk('public')->url($file->path));
        }

        // For other disks, stream the file
        return $this->fileManager->downloadFile($file);
    }

    /**
     * View a file by UUID (for images, PDFs, etc.)
     */
    public function view(string $uuid)
    {
        $file = File::where('uuid', $uuid)->firstOrFail();

        // // Check if user has permission to view
        // if (! $this->securityService->canAccess($file)) {
        //     abort(403, 'You do not have permission to view this file.');
        // }

        // For public disk files, redirect to the storage URL
        if ($file->disk === 'public') {
            return redirect(Storage::disk('public')->url($file->path));
        }

        // For other disks, stream the file with inline disposition
        $disk = Storage::disk($file->disk);

        if (! $disk->exists($file->path)) {
            abort(404, 'File not found.');
        }

        $mimeType = $file->mime_type ?? 'application/octet-stream';

        return response($disk->get($file->path))
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="'.$file->original_name.'"');
    }
}
