@extends('layouts.layoutMaster')

@section('title', __('Permissions'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/permissions.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Permissions')"
      :breadcrumbs="[
        ['name' => __('User Management'), 'url' => ''],
        ['name' => __('Permissions'), 'url' => '']
      ]"
      :home-url="url('/')"
    >
      @can('create-permissions')
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addPermissionOffcanvas">
          <i class="bx bx-plus me-1"></i>{{ __('Add Permission') }}
        </button>
      @endcan
      @if(auth()->user()->hasRole('super_admin'))
        <button type="button" class="btn btn-label-primary ms-2" onclick="syncSuperAdmin()">
          <i class="bx bx-sync me-1"></i>{{ __('Sync Super Admin') }}
        </button>
      @endif
    </x-breadcrumb>

    {{-- Permissions Table --}}
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-3">{{ __('Permission List') }}</h5>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">{{ __('Filter by Module') }}</label>
            <select class="form-select" id="moduleFilter">
              <option value="">{{ __('All Modules') }}</option>
              @foreach($modules as $module)
                <option value="{{ $module }}">{{ $module }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table" id="permissionsTable">
          <thead>
            <tr>
              <th>{{ __('ID') }}</th>
              <th>{{ __('Permission Name') }}</th>
              <th>{{ __('Module') }}</th>
              <th>{{ __('Description') }}</th>
              <th>{{ __('Assigned Roles') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

  {{-- Add Permission Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="addPermissionOffcanvas" aria-labelledby="addPermissionOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="addPermissionOffcanvasLabel" class="offcanvas-title">{{ __('Add New Permission') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
      <form id="addPermissionForm">
        <div class="mb-3">
          <label for="name" class="form-label">{{ __('Permission Name') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="name" name="name" placeholder="e.g., create-users" required>
          <small class="form-text text-muted">{{ __('Use kebab-case format: action-resource') }}</small>
        </div>
        
        <div class="mb-3">
          <label for="module" class="form-label">{{ __('Module') }} <span class="text-danger">*</span></label>
          <select class="form-select" id="module" name="module" required>
            <option value="">{{ __('Select Module') }}</option>
            <option value="SystemAdministration">{{ __('System Administration') }}</option>
            <option value="UserManagement">{{ __('User Management') }}</option>
            <option value="HRCore">{{ __('HR Core') }}</option>
            <option value="CRM">{{ __('CRM') }}</option>
            <option value="ProjectManagement">{{ __('Project Management') }}</option>
            <option value="Accounting">{{ __('Accounting') }}</option>
            <option value="Sales">{{ __('Sales') }}</option>
            <option value="Inventory">{{ __('Inventory') }}</option>
            <option value="Organization">{{ __('Organization') }}</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label for="description" class="form-label">{{ __('Description') }}</label>
          <input type="text" class="form-control" id="description" name="description" placeholder="{{ __('What does this permission allow?') }}">
        </div>
        
        <div class="mb-3">
          <label for="sort_order" class="form-label">{{ __('Sort Order') }}</label>
          <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
        </div>
        
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">{{ __('Create Permission') }}</button>
          <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
        </div>
      </form>
    </div>
  </div>


  {{-- Page Data for JavaScript --}}
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('permissions.datatable')),
        store: @json(route('permissions.store')),
        destroy: @json(route('permissions.destroy', ':id')),
        syncSuperAdmin: @json(route('permissions.sync-super-admin'))
      },
      labels: {
        search: @json(__('Search')),
        processing: @json(__('Processing...')),
        lengthMenu: @json(__('Show _MENU_ entries')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        infoEmpty: @json(__('Showing 0 to 0 of 0 entries')),
        emptyTable: @json(__('No data available')),
        paginate: {
          first: @json(__('First')),
          last: @json(__('Last')),
          next: @json(__('Next')),
          previous: @json(__('Previous'))
        },
        confirmDelete: @json(__('Are you sure you want to delete this permission?')),
        deleteWarning: @json(__('This action cannot be undone')),
        deleteSuccess: @json(__('Permission deleted successfully')),
        createSuccess: @json(__('Permission created successfully')),
        syncSuccess: @json(__('Super admin permissions synced successfully')),
        error: @json(__('An error occurred. Please try again.')),
        delete: @json(__('Delete')),
        confirmSync: @json(__('This will sync all permissions to super admin role. Continue?'))
      }
    };
  </script>
@endsection