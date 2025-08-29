@extends('layouts.layoutMaster')

@section('title', $pageTitle ?? __('Master Data'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@vite(['resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@vite(['resources/assets/vendor/libs/formvalidation/dist/css/formValidation.min.css'])
@stack('vendor-style')
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@vite(['resources/assets/vendor/libs/formvalidation/dist/js/FormValidation.min.js'])
@vite(['resources/assets/vendor/libs/formvalidation/dist/js/plugins/Bootstrap5.min.js'])
@vite(['resources/assets/vendor/libs/formvalidation/dist/js/plugins/AutoFocus.min.js'])
@stack('vendor-script')
@endsection

@section('page-script')
@stack('page-script')
@endsection

@section('content')
<!-- Breadcrumb -->
<div class="row">
  <div class="col-12">
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item">
          <a href="{{ route('master-data.index') }}">{{ __('Master Data') }}</a>
        </li>
        @if(isset($breadcrumbs))
          @foreach($breadcrumbs as $breadcrumb)
            @if($loop->last)
              <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['title'] }}</li>
            @else
              <li class="breadcrumb-item">
                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['title'] }}</a>
              </li>
            @endif
          @endforeach
        @else
          <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle ?? __('Master Data') }}</li>
        @endif
      </ol>
    </nav>
  </div>
</div>

<!-- Page Header -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h4 class="card-title mb-0">
            @if(isset($pageIcon))
              <i class="{{ $pageIcon }} me-2"></i>
            @endif
            {{ $pageTitle ?? __('Master Data') }}
          </h4>
          @if(isset($pageDescription))
            <p class="text-muted mb-0">{{ $pageDescription }}</p>
          @endif
        </div>
        <div class="d-flex gap-2">
          @yield('page-actions')
          
          @if($hasImportExport ?? false)
          <div class="btn-group">
            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bx bx-transfer-alt me-1"></i>
              {{ __('Import/Export') }}
            </button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="{{ route('dataImportExport.index') }}?type={{ $exportType ?? 'master-data' }}">
                <i class="bx bx-download me-2"></i>{{ __('Import Data') }}
              </a></li>
              <li><a class="dropdown-item" href="{{ route('dataImportExport.index') }}?type={{ $exportType ?? 'master-data' }}&action=export">
                <i class="bx bx-upload me-2"></i>{{ __('Export Data') }}
              </a></li>
            </ul>
          </div>
          @endif
          
          <a href="{{ route('master-data.index') }}" class="btn btn-outline-secondary">
            <i class="bx bx-arrow-back"></i>
            {{ __('Back to Dashboard') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main Content -->
<div class="row mt-4">
  <div class="col-12">
    @yield('main-content')
  </div>
</div>

<!-- Modals and Offcanvas -->
@yield('modals')

@endsection

@section('page-script')
<script>
const pageData = {
  urls: @json($urls ?? []),
  labels: {
    confirmDelete: @json(__('Are you sure?')),
    deleteText: @json(__('You won\'t be able to revert this!')),
    confirmButtonText: @json(__('Yes, delete it!')),
    cancelButtonText: @json(__('Cancel')),
    deleted: @json(__('Deleted!')),
    deletedText: @json(__('The record has been deleted.')),
    success: @json(__('Success!')),
    error: @json(__('Error!')),
    saving: @json(__('Saving...')),
    save: @json(__('Save')),
    cancel: @json(__('Cancel')),
    edit: @json(__('Edit')),
    delete: @json(__('Delete')),
    view: @json(__('View')),
    add: @json(__('Add New')),
    search: @json(__('Search...')),
    noRecords: @json(__('No records found')),
    loading: @json(__('Loading...')),
    selectOption: @json(__('Select an option')),
    ...@json($customLabels ?? [])
  },
  hasImportExport: @json($hasImportExport ?? false),
  permissions: @json($permissions ?? [])
};

$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')}
  });

  // Initialize tooltips
  const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Initialize Select2 dropdowns
  $('.select2').select2({
    placeholder: pageData.labels.selectOption,
    allowClear: true
  });

  // Global delete confirmation
  $(document).on('click', '.delete-record', function (e) {
    e.preventDefault();
    const url = $(this).attr('href') || $(this).data('url');
    
    Swal.fire({
      title: pageData.labels.confirmDelete,
      text: pageData.labels.deleteText,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: pageData.labels.confirmButtonText,
      cancelButtonText: pageData.labels.cancelButtonText
    }).then((result) => {
      if (result.isConfirmed) {
        // Create a form and submit it
        const form = $('<form>', {
          method: 'POST',
          action: url
        });
        
        form.append($('<input>', {
          type: 'hidden',
          name: '_token',
          value: $('meta[name="csrf-token"]').attr('content')
        }));
        
        form.append($('<input>', {
          type: 'hidden',
          name: '_method',
          value: 'DELETE'
        }));
        
        $('body').append(form);
        form.submit();
      }
    });
  });

  // Form submission handling
  $(document).on('submit', '.ajax-form', function (e) {
    e.preventDefault();
    
    const $form = $(this);
    const $submitBtn = $form.find('[type="submit"]');
    const originalText = $submitBtn.html();
    
    // Disable submit button and show loading
    $submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>' + pageData.labels.saving);
    
    // Clear previous errors
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback').remove();
    
    $.ajax({
      url: $form.attr('action'),
      method: $form.attr('method') || 'POST',
      data: new FormData(this),
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: pageData.labels.success,
            text: response.message || response.data?.message,
            timer: 3000,
            showConfirmButton: false
          });
          
          // Reload DataTable if exists
          if (typeof window.dataTable !== 'undefined') {
            window.dataTable.ajax.reload(null, false);
          }
          
          // Close modal/offcanvas if exists
          const modal = bootstrap.Modal.getInstance($form.closest('.modal')[0]);
          if (modal) modal.hide();
          
          const offcanvas = bootstrap.Offcanvas.getInstance($form.closest('.offcanvas')[0]);
          if (offcanvas) offcanvas.hide();
          
          // Reset form
          $form[0].reset();
          $form.find('.select2').val(null).trigger('change');
        }
      },
      error: function (xhr) {
        let errorMessage = pageData.labels.error;
        
        if (xhr.status === 422) {
          // Validation errors
          const errors = xhr.responseJSON?.errors || xhr.responseJSON?.data?.errors;
          if (errors) {
            Object.keys(errors).forEach(function (field) {
              const $field = $form.find(`[name="${field}"]`);
              $field.addClass('is-invalid');
              $field.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
            });
            errorMessage = 'Please fix the validation errors and try again.';
          }
        } else {
          errorMessage = xhr.responseJSON?.message || xhr.responseJSON?.data || errorMessage;
        }
        
        Swal.fire({
          icon: 'error',
          title: pageData.labels.error,
          text: errorMessage
        });
      },
      complete: function () {
        // Re-enable submit button
        $submitBtn.prop('disabled', false).html(originalText);
      }
    });
  });
});
</script>
@stack('custom-scripts')
@endsection