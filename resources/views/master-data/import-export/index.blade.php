@extends('master-data.base')

@section('title', __('Master Data Import/Export'))

@push('vendor-style')
@vite(['resources/assets/vendor/libs/dropzone/dropzone.scss'])
@endpush

@push('vendor-script')
@vite(['resources/assets/vendor/libs/dropzone/dropzone.js'])
@endpush

@php
$pageTitle = __('Master Data Import/Export');
$pageDescription = __('Import and export master data across different modules');
$pageIcon = 'bx bx-transfer-alt';
$hasImportExport = true;
@endphp

@section('page-actions')
<div class="btn-group">
  <button type="button" class="btn btn-outline-primary" id="switchToImport" 
          @if($action === 'import') style="display: none;" @endif>
    <i class="bx bx-download me-1"></i>
    {{ __('Switch to Import') }}
  </button>
  <button type="button" class="btn btn-outline-success" id="switchToExport"
          @if($action === 'export') style="display: none;" @endif>
    <i class="bx bx-upload me-1"></i>
    {{ __('Switch to Export') }}
  </button>
</div>
@endsection

@section('main-content')
<div class="row">
  <!-- Master Data Type Selection -->
  <div class="col-md-4 mb-4">
    <div class="card h-100">
      <div class="card-header">
        <h5 class="card-title mb-0">
          <i class="bx bx-list-ul me-2"></i>
          {{ __('Select Data Type') }}
        </h5>
      </div>
      <div class="card-body">
        <div class="list-group list-group-flush">
          @foreach($masterDataTypes as $key => $type)
          <a href="#" class="list-group-item list-group-item-action data-type-item" data-type="{{ $key }}">
            <div class="d-flex align-items-center">
              <div class="avatar avatar-sm me-3">
                <span class="avatar-initial rounded bg-label-primary">
                  <i class="{{ $type['icon'] }} bx-xs"></i>
                </span>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0">{{ $type['label'] }}</h6>
                <small class="text-muted">{{ $type['description'] }}</small>
                @if(isset($type['module']) && $type['module'] !== 'core')
                  <span class="badge bg-label-info ms-2">{{ $type['module'] }}</span>
                @endif
              </div>
            </div>
          </a>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <!-- Import/Export Interface -->
  <div class="col-md-8">
    <!-- Import Interface -->
    <div id="importInterface" @if($action !== 'import') style="display: none;" @endif>
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bx bx-download me-2"></i>
            {{ __('Import Master Data') }}
          </h5>
          <p class="text-muted mb-0">{{ __('Upload a file to import master data into the system') }}</p>
        </div>
        <div class="card-body">
          <!-- Selected Type Info -->
          <div id="selectedTypeInfo" class="alert alert-info" style="display: none;">
            <div class="d-flex align-items-center">
              <i id="selectedTypeIcon" class="bx bx-data me-2"></i>
              <div>
                <strong id="selectedTypeLabel">{{ __('No type selected') }}</strong>
                <div class="small" id="selectedTypeDescription"></div>
              </div>
            </div>
          </div>

          <!-- Import Options -->
          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label">{{ __('Import Mode') }}</label>
              <select class="form-select" id="importMode">
                <option value="insert">{{ __('Insert Only (Skip Duplicates)') }}</option>
                <option value="update">{{ __('Update Only (Skip New)') }}</option>
                <option value="upsert">{{ __('Insert & Update (Recommended)') }}</option>
                <option value="replace">{{ __('Replace All Data (Dangerous)') }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Error Handling') }}</label>
              <select class="form-select" id="errorHandling">
                <option value="skip">{{ __('Skip Errors & Continue') }}</option>
                <option value="stop">{{ __('Stop on First Error') }}</option>
                <option value="collect">{{ __('Collect All Errors') }}</option>
              </select>
            </div>
          </div>

          <!-- File Upload -->
          <div class="mb-4">
            <label class="form-label">{{ __('Upload File') }}</label>
            <div id="importDropzone" class="dropzone">
              <div class="dz-message">
                <i class="bx bx-cloud-upload display-4 text-muted"></i>
                <h5>{{ __('Drop files here or click to upload') }}</h5>
                <p class="text-muted">{{ __('Supported formats: CSV, Excel (.xlsx, .xls)') }}</p>
              </div>
            </div>
          </div>

          <!-- Template Download -->
          <div class="mb-4">
            <h6>{{ __('Need a template?') }}</h6>
            <p class="text-muted">{{ __('Download a template file with the correct format for your data type.') }}</p>
            <button type="button" class="btn btn-outline-secondary" id="downloadTemplate" disabled>
              <i class="bx bx-download me-1"></i>
              {{ __('Download Template') }}
            </button>
          </div>

          <!-- Import Button -->
          <button type="button" class="btn btn-primary" id="startImport" disabled>
            <i class="bx bx-play-circle me-1"></i>
            {{ __('Start Import') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Export Interface -->
    <div id="exportInterface" @if($action !== 'export') style="display: none;" @endif>
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">
            <i class="bx bx-upload me-2"></i>
            {{ __('Export Master Data') }}
          </h5>
          <p class="text-muted mb-0">{{ __('Export master data from the system to a file') }}</p>
        </div>
        <div class="card-body">
          <!-- Selected Type Info -->
          <div id="exportSelectedTypeInfo" class="alert alert-info" style="display: none;">
            <div class="d-flex align-items-center">
              <i id="exportSelectedTypeIcon" class="bx bx-data me-2"></i>
              <div>
                <strong id="exportSelectedTypeLabel">{{ __('No type selected') }}</strong>
                <div class="small" id="exportSelectedTypeDescription"></div>
              </div>
            </div>
          </div>

          <!-- Export Options -->
          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label">{{ __('Export Format') }}</label>
              <select class="form-select" id="exportFormat">
                <option value="xlsx">{{ __('Excel (.xlsx)') }}</option>
                <option value="csv">{{ __('CSV (.csv)') }}</option>
                <option value="pdf">{{ __('PDF (.pdf)') }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('Include') }}</label>
              <select class="form-select" id="exportInclude">
                <option value="active">{{ __('Active Records Only') }}</option>
                <option value="all">{{ __('All Records') }}</option>
                <option value="inactive">{{ __('Inactive Records Only') }}</option>
              </select>
            </div>
          </div>

          <!-- Date Range Filter -->
          <div class="row mb-4">
            <div class="col-md-6">
              <label class="form-label">{{ __('From Date') }}</label>
              <input type="date" class="form-control" id="exportFromDate">
            </div>
            <div class="col-md-6">
              <label class="form-label">{{ __('To Date') }}</label>
              <input type="date" class="form-control" id="exportToDate">
            </div>
          </div>

          <!-- Additional Filters -->
          <div class="mb-4">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="includeMetadata" checked>
              <label class="form-check-label" for="includeMetadata">
                {{ __('Include metadata (created_at, updated_at, etc.)') }}
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="includeRelations">
              <label class="form-check-label" for="includeRelations">
                {{ __('Include related data') }}
              </label>
            </div>
          </div>

          <!-- Export Button -->
          <button type="button" class="btn btn-success" id="startExport" disabled>
            <i class="bx bx-download me-1"></i>
            {{ __('Start Export') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Progress Modal -->
<div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="progressTitle">{{ __('Processing...') }}</h5>
      </div>
      <div class="modal-body">
        <div class="progress mb-3">
          <div class="progress-bar" role="progressbar" style="width: 0%" id="progressBar"></div>
        </div>
        <div class="text-center">
          <div id="progressStatus">{{ __('Initializing...') }}</div>
          <div class="small text-muted" id="progressDetails"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="progressCancel">
          {{ __('Cancel') }}
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('custom-scripts')
<script>
const pageData = {
  urls: {
    template: '{{ route("master-data.import-export.template") }}',
    import: '{{ route("master-data.import-export.import") }}',
    export: '{{ route("master-data.import-export.export") }}',
    status: '{{ route("master-data.import-export.status") }}'
  },
  labels: {
    selectDataType: @json(__('Please select a data type first')),
    uploadFile: @json(__('Please upload a file first')),
    noTypeSelected: @json(__('No type selected')),
    processing: @json(__('Processing...')),
    importing: @json(__('Importing Data...')),
    exporting: @json(__('Exporting Data...')),
    completed: @json(__('Completed Successfully!')),
    failed: @json(__('Operation Failed')),
    downloading: @json(__('Downloading...')),
    ...pageData.labels
  },
  masterDataTypes: @json($masterDataTypes),
  selectedType: @json($selectedType),
  currentAction: @json($action)
};

$(function () {
  initializeImportExport();
});

function initializeImportExport() {
  let selectedType = pageData.selectedType !== 'all' ? pageData.selectedType : null;
  let uploadedFile = null;
  let currentJob = null;

  // Initialize dropzone
  const importDropzone = new Dropzone('#importDropzone', {
    url: pageData.urls.import,
    autoProcessQueue: false,
    maxFiles: 1,
    acceptedFiles: '.csv,.xlsx,.xls',
    addRemoveLinks: true,
    init: function() {
      this.on('addedfile', function(file) {
        uploadedFile = file;
        updateButtonStates();
      });
      this.on('removedfile', function() {
        uploadedFile = null;
        updateButtonStates();
      });
    }
  });

  // Data type selection
  $('.data-type-item').on('click', function(e) {
    e.preventDefault();
    
    $('.data-type-item').removeClass('active');
    $(this).addClass('active');
    
    selectedType = $(this).data('type');
    updateSelectedType();
    updateButtonStates();
  });

  // Interface switching
  $('#switchToImport').on('click', function() {
    $('#exportInterface').hide();
    $('#importInterface').show();
    $(this).hide();
    $('#switchToExport').show();
  });

  $('#switchToExport').on('click', function() {
    $('#importInterface').hide();
    $('#exportInterface').show();
    $(this).hide();
    $('#switchToImport').show();
  });

  // Template download
  $('#downloadTemplate').on('click', function() {
    if (!selectedType) {
      alert(pageData.labels.selectDataType);
      return;
    }
    
    window.open(pageData.urls.template + '?type=' + selectedType, '_blank');
  });

  // Start import
  $('#startImport').on('click', function() {
    if (!selectedType) {
      alert(pageData.labels.selectDataType);
      return;
    }
    
    if (!uploadedFile) {
      alert(pageData.labels.uploadFile);
      return;
    }
    
    startImport();
  });

  // Start export
  $('#startExport').on('click', function() {
    if (!selectedType) {
      alert(pageData.labels.selectDataType);
      return;
    }
    
    startExport();
  });

  function updateSelectedType() {
    const type = pageData.masterDataTypes[selectedType];
    if (type) {
      // Update import interface
      $('#selectedTypeIcon').attr('class', type.icon + ' me-2');
      $('#selectedTypeLabel').text(type.label);
      $('#selectedTypeDescription').text(type.description);
      $('#selectedTypeInfo').show();
      
      // Update export interface
      $('#exportSelectedTypeIcon').attr('class', type.icon + ' me-2');
      $('#exportSelectedTypeLabel').text(type.label);
      $('#exportSelectedTypeDescription').text(type.description);
      $('#exportSelectedTypeInfo').show();
    }
  }

  function updateButtonStates() {
    const hasType = selectedType !== null;
    const hasFile = uploadedFile !== null;
    
    $('#downloadTemplate').prop('disabled', !hasType);
    $('#startImport').prop('disabled', !hasType || !hasFile);
    $('#startExport').prop('disabled', !hasType);
  }

  function startImport() {
    const formData = new FormData();
    formData.append('type', selectedType);
    formData.append('file', uploadedFile);
    formData.append('mode', $('#importMode').val());
    formData.append('error_handling', $('#errorHandling').val());
    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
    
    showProgress(pageData.labels.importing);
    
    $.ajax({
      url: pageData.urls.import,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        if (response.job_id) {
          currentJob = response.job_id;
          pollStatus();
        } else {
          hideProgress();
          showSuccess(response.message);
        }
      },
      error: function(xhr) {
        hideProgress();
        showError(xhr.responseJSON?.message || 'Import failed');
      }
    });
  }

  function startExport() {
    const data = {
      type: selectedType,
      format: $('#exportFormat').val(),
      include: $('#exportInclude').val(),
      from_date: $('#exportFromDate').val(),
      to_date: $('#exportToDate').val(),
      include_metadata: $('#includeMetadata').is(':checked'),
      include_relations: $('#includeRelations').is(':checked'),
      _token: $('meta[name="csrf-token"]').attr('content')
    };
    
    showProgress(pageData.labels.exporting);
    
    $.ajax({
      url: pageData.urls.export,
      method: 'POST',
      data: data,
      success: function(response) {
        if (response.job_id) {
          currentJob = response.job_id;
          pollStatus();
        } else if (response.download_url) {
          hideProgress();
          window.open(response.download_url, '_blank');
          showSuccess('Export completed successfully');
        }
      },
      error: function(xhr) {
        hideProgress();
        showError(xhr.responseJSON?.message || 'Export failed');
      }
    });
  }

  function showProgress(title) {
    $('#progressTitle').text(title);
    $('#progressBar').css('width', '0%');
    $('#progressStatus').text('Initializing...');
    $('#progressDetails').text('');
    $('#progressModal').modal('show');
  }

  function hideProgress() {
    $('#progressModal').modal('hide');
  }

  function pollStatus() {
    if (!currentJob) return;
    
    $.get(pageData.urls.status, { job_id: currentJob })
      .done(function(response) {
        updateProgress(response);
        
        if (response.status === 'completed') {
          hideProgress();
          if (response.download_url) {
            window.open(response.download_url, '_blank');
          }
          showSuccess(response.message);
          currentJob = null;
        } else if (response.status === 'failed') {
          hideProgress();
          showError(response.message);
          currentJob = null;
        } else {
          setTimeout(pollStatus, 2000);
        }
      })
      .fail(function() {
        setTimeout(pollStatus, 2000);
      });
  }

  function updateProgress(status) {
    const progress = status.progress || 0;
    $('#progressBar').css('width', progress + '%');
    $('#progressStatus').text(status.message || 'Processing...');
    $('#progressDetails').text(status.details || '');
  }

  function showSuccess(message) {
    Swal.fire({
      icon: 'success',
      title: pageData.labels.completed,
      text: message,
      timer: 3000,
      showConfirmButton: false
    });
  }

  function showError(message) {
    Swal.fire({
      icon: 'error',
      title: pageData.labels.failed,
      text: message
    });
  }

  // Initialize with selected type if provided
  if (selectedType) {
    $(`.data-type-item[data-type="${selectedType}"]`).addClass('active');
    updateSelectedType();
    updateButtonStates();
  }
}
</script>
@endpush