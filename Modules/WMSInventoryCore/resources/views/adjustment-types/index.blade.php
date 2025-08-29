@extends('layouts.layoutMaster')

@section('title', __('Adjustment Types'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('wmsinventorycore.adjustment-types.data')),
        store: @json(route('wmsinventorycore.adjustmenttypes.store')),
        update: @json(route('wmsinventorycore.adjustmenttypes.update', ['adjustmentType' => '__TYPE_ID__'])),
        destroy: @json(route('wmsinventorycore.adjustmenttypes.destroy', ['adjustmentType' => '__TYPE_ID__']))
      },
      labels: {
        processing: @json(__('Processing...')),
        search: @json(__('Search')),
        show: @json(__('Show')),
        entries: @json(__('entries')),
        showing: @json(__('Showing')),
        to: @json(__('to')),
        of: @json(__('of')),
        first: @json(__('First')),
        previous: @json(__('Previous')),
        next: @json(__('Next')),
        last: @json(__('Last')),
        loading: @json(__('Loading...')),
        noMatchingRecords: @json(__('No matching records found')),
        noDataAvailable: @json(__('No data available in table')),
        success: @json(__('Success!')),
        error: @json(__('Error!')),
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__('This action cannot be undone. The adjustment type will be permanently deleted.')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        adjustmentTypeCreated: @json(__('Adjustment type has been created successfully')),
        adjustmentTypeUpdated: @json(__('Adjustment type has been updated successfully')),
        adjustmentTypeDeleted: @json(__('Adjustment type has been deleted successfully')),
        failedToCreate: @json(__('Failed to create adjustment type')),
        failedToUpdate: @json(__('Failed to update adjustment type')),
        failedToDelete: @json(__('Failed to delete adjustment type'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-adjustment-types.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Adjustment Types')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Adjustment Types') }}</h5>
      @can('wmsinventory.create-adjustment-type')
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddAdjustmentType">
          <i class="bx bx-plus"></i> {{ __('Add New Adjustment Type') }}
        </button>
      @endcan
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table table-bordered datatables-adjustment-types">
      <thead>
        <tr>
          <th>{{ __('ID') }}</th>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Description') }}</th>
          <th>{{ __('Effect') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@can('wmsinventory.create-adjustment-type')
<!-- Add Adjustment Type Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdjustmentType" aria-labelledby="offcanvasAddAdjustmentTypeLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddAdjustmentTypeLabel" class="offcanvas-title">{{ __('Add New Adjustment Type') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="addAdjustmentTypeForm" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label" for="name">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" required placeholder="{{ __('e.g., Damage, Return, Correction') }}" />
        <div class="invalid-feedback">{{ __('Please enter an adjustment type name.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="operation_type">{{ __('Effect Type') }} <span class="text-danger">*</span></label>
        <select class="form-select" id="operation_type" name="effect" required>
          <option value="">{{ __('Select Effect Type') }}</option>
          <option value="increase">{{ __('Increase') }}</option>
          <option value="decrease">{{ __('Decrease') }}</option>
        </select>
        <div class="invalid-feedback">{{ __('Please select an effect type.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="description">{{ __('Description') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">{{ __('Submit') }}</button>
      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
    </form>
  </div>
</div>
@endcan

@can('wmsinventory.edit-adjustment-type')
<!-- Edit Adjustment Type Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditAdjustmentType" aria-labelledby="offcanvasEditAdjustmentTypeLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditAdjustmentTypeLabel" class="offcanvas-title">{{ __('Edit Adjustment Type') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="editAdjustmentTypeForm" class="needs-validation" novalidate>
      <input type="hidden" id="edit_id" name="id" />
      <div class="mb-3">
        <label class="form-label" for="edit_name">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit_name" name="name" required />
        <div class="invalid-feedback">{{ __('Please enter an adjustment type name.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_operation_type">{{ __('Effect Type') }} <span class="text-danger">*</span></label>
        <select class="form-select" id="edit_operation_type" name="effect" required>
          <option value="">{{ __('Select Effect Type') }}</option>
          <option value="increase">{{ __('Increase') }}</option>
          <option value="decrease">{{ __('Decrease') }}</option>
        </select>
        <div class="invalid-feedback">{{ __('Please select an effect type.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_description">{{ __('Description') }}</label>
        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
      </div>
      <button type="submit" class="btn btn-primary me-sm-3 me-1 data-submit">{{ __('Update') }}</button>
      <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
    </form>
  </div>
</div>
@endcan
@endsection
