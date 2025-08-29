@extends('layouts.layoutMaster')

@section('title', __('Roles'))

<!-- Vendor Styles -->
@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/@form-validation/form-validation.scss',
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
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/roles.js'])
@endsection

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">
    {{-- Breadcrumb Component --}}
    <x-breadcrumb
      :title="__('Roles')"
      :breadcrumbs="[
        ['name' => __('User Management'), 'url' => ''],
        ['name' => __('Roles'), 'url' => '']
      ]"
      :home-url="url('/')"
    >
      <x-slot name="actions">
        @can('create-roles')
          <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#addRoleOffcanvas">
            <i class="bx bx-plus me-1"></i>{{ __('Add Role') }}
          </button>
         @endcan
      </x-slot>
    </x-breadcrumb>

    {{-- Roles Table --}}
    <div class="card">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">{{ __('Role List') }}</h5>
      </div>
      <div class="card-datatable table-responsive">
        <table class="table" id="rolesTable">
          <thead>
            <tr>
              <th>{{ __('Role Name') }}</th>
              <th>{{ __('Description') }}</th>
              <th>{{ __('Users') }}</th>
              <th>{{ __('Permissions') }}</th>
              <th>{{ __('Status') }}</th>
              <th>{{ __('Actions') }}</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

    @if($settings->is_helper_text_enabled)
      <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
        <h6 class="alert-heading mb-1">
          <i class="bx bx-info-circle me-2"></i>{{ __('Important Notice') }}
        </h6>
        <p class="mb-0">
          {{ __('Do not delete the built-in system roles') }} </strong>.
          {{ __('Deleting these roles will cause the system to malfunction.') }}
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
      </div>
    @endif
  </div>

  {{-- Add Role Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="addRoleOffcanvas" aria-labelledby="addRoleOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="addRoleOffcanvasLabel" class="offcanvas-title">{{ __('Add New Role') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
      <form id="addRoleForm">
        <div class="mb-3">
          <label for="name" class="form-label">{{ __('Role Name') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('e.g., Sales Manager') }}" required>
        </div>

        <div class="mb-3">
          <label for="description" class="form-label">{{ __('Description') }}</label>
          <textarea class="form-control" id="description" name="description" rows="3" placeholder="{{ __('Describe this role') }}"></textarea>
        </div>

        <h6 class="mb-3">{{ __('Role Settings') }}</h6>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_multiple_check_in_enabled" name="is_multiple_check_in_enabled" value="1">
            <label class="form-check-label" for="is_multiple_check_in_enabled">
              {{ __('Enable Multiple Check-In/Out') }}
            </label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_mobile_app_access_enabled" name="is_mobile_app_access_enabled" value="1" checked>
            <label class="form-check-label" for="is_mobile_app_access_enabled">
              {{ __('Enable Mobile App Access') }}
            </label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_web_access_enabled" name="is_web_access_enabled" value="1" checked>
            <label class="form-check-label" for="is_web_access_enabled">
              {{ __('Enable Web App Access') }}
            </label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_location_activity_tracking_enabled" name="is_location_activity_tracking_enabled" value="1">
            <label class="form-check-label" for="is_location_activity_tracking_enabled">
              {{ __('Enable Location Activity Tracking') }}
            </label>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">{{ __('Create Role') }}</button>
          <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Edit Role Offcanvas --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="editRoleOffcanvas" aria-labelledby="editRoleOffcanvasLabel">
    <div class="offcanvas-header">
      <h5 id="editRoleOffcanvasLabel" class="offcanvas-title">{{ __('Edit Role') }}</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
      <form id="editRoleForm">
        <input type="hidden" id="editRoleId" name="role_id">

        <div class="mb-3">
          <label for="editName" class="form-label">{{ __('Role Name') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="editName" name="name" required>
        </div>

        <div class="mb-3">
          <label for="editDescription" class="form-label">{{ __('Description') }}</label>
          <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
        </div>

        <h6 class="mb-3">{{ __('Role Settings') }}</h6>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="edit_is_multiple_check_in_enabled" name="is_multiple_check_in_enabled" value="1">
            <label class="form-check-label" for="edit_is_multiple_check_in_enabled">
              {{ __('Enable Multiple Check-In/Out') }}
            </label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="edit_is_mobile_app_access_enabled" name="is_mobile_app_access_enabled" value="1">
            <label class="form-check-label" for="edit_is_mobile_app_access_enabled">
              {{ __('Enable Mobile App Access') }}
            </label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="edit_is_web_access_enabled" name="is_web_access_enabled" value="1">
            <label class="form-check-label" for="edit_is_web_access_enabled">
              {{ __('Enable Web App Access') }}
            </label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="edit_is_location_activity_tracking_enabled" name="is_location_activity_tracking_enabled" value="1">
            <label class="form-check-label" for="edit_is_location_activity_tracking_enabled">
              {{ __('Enable Location Activity Tracking') }}
            </label>
          </div>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-fill">{{ __('Update Role') }}</button>
          <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
        </div>
      </form>
    </div>
  </div>

  {{-- Page Data for JavaScript --}}
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('roles.datatable')),
        store: @json(route('roles.store')),
        edit: @json(route('roles.edit', ':id')),
        update: @json(route('roles.update', ':id')),
        destroy: @json(route('roles.destroy', ':id'))
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
        confirmDelete: @json(__('Are you sure you want to delete this role?')),
        deleteWarning: @json(__('All permissions will be revoked from this role')),
        deleteSuccess: @json(__('Role deleted successfully')),
        createSuccess: @json(__('Role created successfully')),
        updateSuccess: @json(__('Role updated successfully')),
        error: @json(__('An error occurred. Please try again.')),
        delete: @json(__('Delete'))
      }
    };
  </script>
@endsection
