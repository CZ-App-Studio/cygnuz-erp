@extends('layouts.layoutMaster')

@section('title', __('Product Units'))

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
        unitsData: @json(route('wmsinventorycore.units.data')),
        unitsStore: @json(route('wmsinventorycore.units.store')),
        unitsUpdate: @json(route('wmsinventorycore.units.update', ['unit' => '__UNIT_ID__'])),
        unitsDelete: @json(route('wmsinventorycore.units.destroy', ['unit' => '__UNIT_ID__']))
      },
      labels: {
        success: @json(__('Success!')),
        error: @json(__('Error!')),
        unitCreatedSuccessfully: @json(__('Unit created successfully.')),
        unitUpdatedSuccessfully: @json(__('Unit updated successfully.')),
        unitDeletedSuccessfully: @json(__('Unit deleted successfully.')),
        failedToCreateUnit: @json(__('Failed to create unit.')),
        failedToUpdateUnit: @json(__('Failed to update unit.')),
        failedToDeleteUnit: @json(__('Failed to delete unit.')),
        areYouSure: @json(__('Are you sure?')),
        deleteUnitWarning: @json(__('This action cannot be undone. The unit will be permanently deleted.')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleted: @json(__('Deleted!'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-units.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Product Units')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Units') }}</h5>
      @can('wmsinventory.create-unit')
        <button type="button" class="btn btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddUnit">
          <i class="bx bx-plus"></i> {{ __('Add New Unit') }}
        </button>
      @endcan
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table table-bordered datatables-units">
      <thead>
        <tr>
          <th>{{ __('ID') }}</th>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Code') }}</th>
          <th>{{ __('Description') }}</th>
          <th>{{ __('Products') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Add Unit Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUnit" aria-labelledby="offcanvasAddUnitLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasAddUnitLabel" class="offcanvas-title">{{ __('Add New Unit') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="addUnitForm" class="needs-validation" novalidate>
      <div class="mb-3">
        <label class="form-label" for="name">{{ __('Unit Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" required placeholder="{{ __('e.g., Piece, Box, Kilogram') }}" />
        <div class="invalid-feedback">{{ __('Please enter a unit name.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="code">{{ __('Unit Code') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="code" name="code" required placeholder="{{ __('e.g., PCS, BOX, KG') }}" />
        <div class="invalid-feedback">{{ __('Please enter a unit code.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="description">{{ __('Description') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill">{{ __('Submit') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>

@can('wmsinventory.edit-unit')
<!-- Edit Unit Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasEditUnit" aria-labelledby="offcanvasEditUnitLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasEditUnitLabel" class="offcanvas-title">{{ __('Edit Unit') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <form id="editUnitForm" class="needs-validation" novalidate>
      <input type="hidden" id="edit_id" name="id" />
      <div class="mb-3">
        <label class="form-label" for="edit_name">{{ __('Unit Name') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit_name" name="name" required />
        <div class="invalid-feedback">{{ __('Please enter a unit name.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_code">{{ __('Unit Code') }} <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="edit_code" name="code" required />
        <div class="invalid-feedback">{{ __('Please enter a unit code.') }}</div>
      </div>
      <div class="mb-3">
        <label class="form-label" for="edit_description">{{ __('Description') }}</label>
        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill">{{ __('Update') }}</button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
      </div>
    </form>
  </div>
</div>
@endcan
@endsection
