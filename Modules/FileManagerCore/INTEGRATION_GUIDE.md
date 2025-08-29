# FileManagerCore Integration Guide

## 📋 Overview

The **FileManagerCore** module provides a comprehensive, enterprise-grade file management system for the ERP application. This guide will help you integrate file management capabilities into existing modules.

## 🚀 Quick Start

### 1. Basic Integration

Add file attachment capabilities to any model:

```php
<?php

namespace Modules\YourModule\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\FileManagerCore\Traits\HasFiles;
use Modules\FileManagerCore\Traits\TracksStorage;

class YourModel extends Model
{
    use HasFiles, TracksStorage;

    // Your model now has file management capabilities
}
```

### 2. File Upload Example

```php
<?php

namespace Modules\YourModule\Http\Controllers;

use Illuminate\Http\Request;
use Modules\FileManagerCore\Services\FileManagerService;
use Modules\FileManagerCore\DTO\FileUploadRequest;
use Modules\FileManagerCore\Enums\FileType;
use Modules\FileManagerCore\Enums\FileVisibility;

class YourController extends Controller
{
    public function uploadFile(Request $request, YourModel $model)
    {
        $fileManager = app(FileManagerService::class);

        $uploadRequest = FileUploadRequest::fromRequest(
            $request->file('document'),
            FileType::EMPLOYEE_DOCUMENT,
            YourModel::class,
            $model->id
        )->withVisibility(FileVisibility::PRIVATE)
          ->withDescription($request->input('description'))
          ->withMetadata(['department' => $model->department_id]);

        $file = $fileManager->uploadFile($uploadRequest);

        return response()->json([
            'success' => true,
            'file' => $file,
            'message' => 'File uploaded successfully'
        ]);
    }
}
```

## 📚 Core Components Reference

### Available File Types

```php
use Modules\FileManagerCore\Enums\FileType;

// Profile & Employee Management
FileType::EMPLOYEE_PROFILE_PICTURE
FileType::EMPLOYEE_DOCUMENT
FileType::ONBOARDING_FILE

// Leave & Attendance
FileType::LEAVE_DOCUMENT
FileType::ATTENDANCE_DOCUMENT

// Communication
FileType::CHAT_FILE

// Accounting & Finance
FileType::INVOICE_ATTACHMENT
FileType::PROPOSAL_ATTACHMENT
FileType::EXPENSE_RECEIPT

// Project Management
FileType::PROJECT_FILE

// Assets & Company
FileType::COMPANY_LOGO
FileType::PRODUCT_IMAGE
FileType::ASSET_IMAGE
FileType::ASSET_DOCUMENT

// Compliance & Training
FileType::AUDIT_DOCUMENT
FileType::TRAINING_MATERIAL
FileType::POLICY_DOCUMENT
FileType::PAYROLL_DOCUMENT

// Desktop Tracking
FileType::DESKTOP_SCREENSHOT

// General
FileType::GENERAL
```

### File Visibility Options

```php
use Modules\FileManagerCore\Enums\FileVisibility;

FileVisibility::PUBLIC     // Accessible to anyone with link
FileVisibility::PRIVATE    // Only accessible to specific users
FileVisibility::INTERNAL   // Accessible to all company members
```

### File Status Management

```php
use Modules\FileManagerCore\Enums\FileStatus;

FileStatus::UPLOADING   // File is being uploaded
FileStatus::ACTIVE      // File is ready and accessible
FileStatus::PROCESSING  // File is being processed
FileStatus::ARCHIVED    // File is archived but accessible
FileStatus::DELETED     // File is soft-deleted
FileStatus::FAILED      // Upload or processing failed
```

## 🔧 Integration Patterns

### Pattern 1: Basic File Attachment

```php
// In your model
class Employee extends Model
{
    use HasFiles;

    public function getProfilePictureUrl(): ?string
    {
        $profilePic = $this->fileByType(FileType::EMPLOYEE_PROFILE_PICTURE);
        return $profilePic ? app(FileManagerInterface::class)->getFileUrl($profilePic) : null;
    }
}

// In your controller
public function updateProfilePicture(Request $request, Employee $employee)
{
    $uploadRequest = FileUploadRequest::fromRequest(
        $request->file('profile_picture'),
        FileType::EMPLOYEE_PROFILE_PICTURE,
        Employee::class,
        $employee->id
    )->withName($employee->employee_code . '_profile')
      ->withVisibility(FileVisibility::INTERNAL);

    $file = app(FileManagerService::class)->uploadFile($uploadRequest);

    return response()->json(['success' => true, 'file_url' => $file->url]);
}
```

### Pattern 2: Document Management

```php
// Multiple document types for a model
class LeaveApplication extends Model
{
    use HasFiles;

    public function getDocuments()
    {
        return $this->filesByType(FileType::LEAVE_DOCUMENT);
    }

    public function getMedicalCertificates()
    {
        return $this->files()->where('metadata->document_type', 'medical_certificate')->get();
    }
}

// Upload with metadata
$uploadRequest = FileUploadRequest::fromRequest(
    $file,
    FileType::LEAVE_DOCUMENT,
    LeaveApplication::class,
    $leaveApp->id
)->withMetadata([
    'document_type' => 'medical_certificate',
    'leave_type' => $leaveApp->leave_type,
    'submitted_by' => auth()->id()
]);
```

### Pattern 3: Storage Quota Management

```php
// For user models
class User extends Model
{
    use TracksStorage;

    public function canUploadFile($fileSize): bool
    {
        return $this->canUploadFile($fileSize, 'public');
    }

    public function getStorageInfo(): array
    {
        return $this->getFormattedStorageUsage('public');
    }
}

// In controller - check quota before upload
public function uploadDocument(Request $request)
{
    $user = auth()->user();
    $fileSize = $request->file('document')->getSize();

    if (!$user->canUploadFile($fileSize)) {
        return response()->json([
            'error' => 'Storage quota exceeded'
        ], 422);
    }

    // Proceed with upload...
}
```

### Pattern 4: File Sharing

```php
use Modules\FileManagerCore\Services\FileSecurityService;

public function shareFile(Request $request, File $file)
{
    $securityService = app(FileSecurityService::class);

    $share = $securityService->createShareLink(
        $file,
        ['view', 'download'], // permissions
        now()->addDays(7),    // expires in 7 days
        10                    // max 10 downloads
    );

    return response()->json([
        'share_url' => $share->getShareUrl(),
        'expires_at' => $share->expires_at,
        'remaining_downloads' => $share->getRemainingDownloads()
    ]);
}
```

## 🎯 Common Integration Examples

### HRCore Module Integration

```php
// Employee model update
class Employee extends Model
{
    use HasFiles, TracksStorage;

    public function getProfilePicture(): ?File
    {
        return $this->fileByType(FileType::EMPLOYEE_PROFILE_PICTURE);
    }

    public function getDocuments(): Collection
    {
        return $this->filesByType(FileType::EMPLOYEE_DOCUMENT);
    }

    public function getOnboardingFiles(): Collection
    {
        return $this->filesByType(FileType::ONBOARDING_FILE);
    }
}

// Controller methods
public function uploadEmployeeDocument(Request $request, Employee $employee)
{
    $request->validate([
        'document' => 'required|file|max:10240', // 10MB max
        'document_type' => 'required|string',
        'description' => 'nullable|string'
    ]);

    $uploadRequest = FileUploadRequest::fromRequest(
        $request->file('document'),
        FileType::EMPLOYEE_DOCUMENT,
        Employee::class,
        $employee->id
    )->withDescription($request->input('description'))
      ->withMetadata([
          'document_type' => $request->input('document_type'),
          'uploaded_by' => auth()->id(),
          'department' => $employee->department_id
      ]);

    $file = app(FileManagerService::class)->uploadFile($uploadRequest);

    return response()->json([
        'success' => true,
        'file' => $file->only(['id', 'name', 'size', 'mime_type', 'created_at'])
    ]);
}
```

### Leave Management Integration

```php
// Leave model
class Leave extends Model
{
    use HasFiles;

    public function getSupportingDocuments(): Collection
    {
        return $this->filesByType(FileType::LEAVE_DOCUMENT);
    }
}

// Controller
public function attachDocument(Request $request, Leave $leave)
{
    if (!$leave->status->canAttachDocuments()) {
        return response()->json(['error' => 'Cannot attach documents to this leave'], 422);
    }

    $uploadRequest = FileUploadRequest::fromRequest(
        $request->file('document'),
        FileType::LEAVE_DOCUMENT,
        Leave::class,
        $leave->id
    )->withMetadata([
        'leave_type' => $leave->leave_type,
        'employee_id' => $leave->employee_id
    ]);

    $file = app(FileManagerService::class)->uploadFile($uploadRequest);

    // Auto-approve leave if medical certificate attached
    if ($request->input('document_type') === 'medical_certificate') {
        $leave->update(['status' => 'auto_approved']);
    }

    return response()->json(['success' => true, 'file' => $file]);
}
```

### Project Management Integration

```php
// Project model
class Project extends Model
{
    use HasFiles;

    public function getProjectFiles(): Collection
    {
        return $this->filesByType(FileType::PROJECT_FILE);
    }

    public function getFilesByCategory(string $category): Collection
    {
        return $this->files()
                    ->where('metadata->category', $category)
                    ->get();
    }
}

// Task model
class Task extends Model
{
    use HasFiles;

    public function attachments(): Collection
    {
        return $this->filesByType(FileType::PROJECT_FILE);
    }
}
```

## 🛠️ Utilities & Helpers

### File Search

```php
use Modules\FileManagerCore\DTO\FileSearchRequest;

public function searchFiles(Request $request)
{
    $searchRequest = FileSearchRequest::fromArray($request->all());

    $files = File::search($searchRequest->query)
                 ->when($searchRequest->type, fn($q) => $q->where('metadata->type', $searchRequest->type->value))
                 ->when($searchRequest->userId, fn($q) => $q->where('created_by_id', $searchRequest->userId))
                 ->paginate($searchRequest->perPage);

    return response()->json($files);
}
```

### Bulk Operations

```php
public function deleteMultipleFiles(Request $request)
{
    $fileIds = $request->input('file_ids');
    $fileManager = app(FileManagerService::class);

    $deleted = [];
    $errors = [];

    foreach ($fileIds as $fileId) {
        $file = File::find($fileId);
        if ($file && app(FileSecurityService::class)->canDelete($file)) {
            $fileManager->deleteFile($file);
            $deleted[] = $fileId;
        } else {
            $errors[] = "Cannot delete file {$fileId}";
        }
    }

    return response()->json([
        'deleted' => $deleted,
        'errors' => $errors
    ]);
}
```

### File Download with Security

```php
public function downloadFile(Request $request, string $uuid)
{
    $file = File::where('uuid', $uuid)->firstOrFail();

    if (!app(FileSecurityService::class)->canDownload($file)) {
        abort(403, 'Unauthorized to download this file');
    }

    return app(FileManagerService::class)->downloadFile($file);
}
```

## 🎨 Frontend Integration

### Blade Template Examples

```html
<!-- File upload form -->
<form action="{{ route('files.upload') }}" method="POST" enctype="multipart/form-data">
  @csrf
  <input type="file" name="document" required />
  <select name="file_type">
    <option value="employee_document">Employee Document</option>
    <option value="leave_document">Leave Document</option>
  </select>
  <textarea name="description" placeholder="Description"></textarea>
  <button type="submit">Upload</button>
</form>

<!-- Display attached files -->
@if($model->hasFiles())
<div class="attached-files">
  <h4>Attached Files</h4>
  @foreach($model->files as $file)
  <div class="file-item">
    <i class="{{ $file->icon }}"></i>
    <span>{{ $file->original_name }}</span>
    <span class="file-size">{{ $file->formatted_size }}</span>
    <a href="{{ route('files.download', $file->uuid) }}" class="btn btn-sm btn-primary"> Download </a>
    @if(auth()->user()->can('delete', $file))
    <button type="button" class="btn btn-sm btn-danger" onclick="deleteFile('{{ $file->uuid }}')">Delete</button>
    @endif
  </div>
  @endforeach
</div>
@endif

<!-- Storage quota display -->
@if($user->hasStorageQuota())
<div class="storage-quota">
  @php $quota = $user->getFormattedStorageUsage() @endphp
  <div class="quota-bar">
    <div class="quota-used" style="width: {{ $quota['usage_percentage'] }}%"></div>
  </div>
  <small> {{ $quota['formatted']['used_space'] }} / {{ $quota['formatted']['total_space'] }} used </small>
</div>
@endif
```

### JavaScript Integration

```javascript
// File upload with progress
function uploadFile(file, fileType, attachableType, attachableId) {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('file_type', fileType);
  formData.append('attachable_type', attachableType);
  formData.append('attachable_id', attachableId);

  return $.ajax({
    url: '/api/files/upload',
    method: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    xhr: function () {
      const xhr = new window.XMLHttpRequest();
      xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          updateProgressBar(percentComplete);
        }
      });
      return xhr;
    }
  });
}

// Drag and drop upload
function initDragDrop() {
  $('.file-drop-zone').on('dragover', function (e) {
    e.preventDefault();
    $(this).addClass('drag-over');
  });

  $('.file-drop-zone').on('drop', function (e) {
    e.preventDefault();
    $(this).removeClass('drag-over');

    const files = e.originalEvent.dataTransfer.files;
    for (let file of files) {
      uploadFile(file, 'general', null, null);
    }
  });
}
```

## 🔒 Security & Permissions

### File Access Control

```php
// In your controller
public function showFile(File $file)
{
    $security = app(FileSecurityService::class);

    if (!$security->canAccess($file)) {
        abort(403, 'Access denied');
    }

    // Log access
    $security->logFileAccess($file, 'view');

    return view('files.show', compact('file'));
}
```

### Role-Based Access

```php
// Share with specific role
$securityService->shareWithRole(
    $file,
    $roleId,
    ['view', 'download'],
    now()->addMonths(3)
);

// Share with specific user
$securityService->shareWithUser(
    $file,
    $user,
    ['view', 'download', 'edit'],
    now()->addWeeks(2)
);
```

## ⚙️ Configuration

### Environment Variables

Add to your `.env` file:

```env
# File Manager Configuration
FILEMANAGER_DEFAULT_DISK=public
FILEMANAGER_MAX_FILE_SIZE=10240
FILEMANAGER_USER_QUOTA=1073741824
FILEMANAGER_DEPT_QUOTA=10737418240
FILEMANAGER_VERSIONING=true
FILEMANAGER_MAX_VERSIONS=5
FILEMANAGER_VIRUS_SCAN=false
FILEMANAGER_ENCRYPT_FILES=false
FILEMANAGER_THUMBNAIL_DISK=public
```

### Module Configuration

The module configuration is available at `config/filemanagercore.php` with comprehensive settings for:

- Storage providers
- File size limits
- Allowed MIME types
- Thumbnail settings
- Security options
- Cleanup schedules

## 🐛 Troubleshooting

### Common Issues

1. **Files not uploading**: Check file size limits and MIME type restrictions
2. **Permission denied**: Verify user has appropriate file access permissions
3. **Storage quota exceeded**: Check user/department storage limits
4. **File not found**: Ensure file exists and is not soft-deleted

### Debug Commands

```bash
# Check module status
php artisan module:list

# Run migrations
php artisan module:migrate FileManagerCore

# Check file system
php artisan storage:link
```

## 📊 Performance Tips

1. **Use eager loading** for file relationships: `$model->load('files')`
2. **Implement caching** for frequently accessed files
3. **Use queues** for thumbnail generation and file processing
4. **Index database queries** on file metadata when searching
5. **Implement CDN** for public files

## 🚀 Advanced Usage

### Custom File Types

```php
// Extend FileType enum in your module
// Note: This is a conceptual example - enums can't be extended in PHP
// Instead, use metadata to categorize files within existing types

$uploadRequest = FileUploadRequest::fromRequest(
    $file,
    FileType::EMPLOYEE_DOCUMENT
)->withMetadata([
    'custom_type' => 'performance_review',
    'review_period' => '2024-Q1'
]);
```

### File Processing Hooks

```php
// Listen for file upload events
Event::listen(FileUploaded::class, function ($event) {
    if ($event->file->isImage()) {
        // Generate thumbnails
        dispatch(new GenerateThumbnailJob($event->file));
    }

    if ($event->file->metadata['scan_for_virus'] ?? false) {
        // Scan for viruses
        dispatch(new VirusScanJob($event->file));
    }
});
```

---

## 📞 Support

For integration support or questions:

1. Check the module's configuration at `Modules/FileManagerCore/config/config.php`
2. Review the models and their relationships
3. Test with the provided patterns and examples
4. Ensure proper service provider registration

The FileManagerCore module is designed to be flexible and extensible. Follow the patterns shown above for seamless integration with your existing modules.
