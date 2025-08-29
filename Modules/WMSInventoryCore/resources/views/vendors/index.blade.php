@extends('layouts.layoutMaster')

@section('title', __('Vendors'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        datatable: @json(route('wmsinventorycore.vendors.data')),
        vendorsShow: @json(route('wmsinventorycore.vendors.show', ['vendor' => '__VENDOR_ID__'])),
        vendorsEdit: @json(route('wmsinventorycore.vendors.edit', ['vendor' => '__VENDOR_ID__'])),
        vendorsUpdate: @json(route('wmsinventorycore.vendors.update', ['vendor' => '__VENDOR_ID__'])),
        vendorsDelete: @json(route('wmsinventorycore.vendors.destroy', ['vendor' => '__VENDOR_ID__'])),
        vendorsCreate: @json(route('wmsinventorycore.vendors.create')),
        vendorsStore: @json(route('wmsinventorycore.vendors.store'))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Vendor has been deleted.')),
        error: @json(__('Error!')),
        couldNotDelete: @json(__('Could not delete vendor.')),
        selectStatus: @json(__('Select Status')),
        success: @json(__('Success!')),
        vendorSaved: @json(__('Vendor has been saved successfully.')),
        errorOccurred: @json(__('An error occurred. Please try again.')),
        saving: @json(__('Saving...')),
        addVendor: @json(__('Add New Vendor')),
        editVendor: @json(__('Edit Vendor'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-vendors.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Vendors'), 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Vendors')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Vendors') }}</h5>
      @can('wmsinventory.create-vendor')
        <a href="{{ route('wmsinventorycore.vendors.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Add New Vendor') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="row mb-4">
      <div class="col-md-4">
        <label class="form-label">{{ __('Filter by Status') }}</label>
        <select id="status-filter" class="select2 form-select" data-placeholder="{{ __('Select Status') }}">
          <option value="">{{ __('All Statuses') }}</option>
          <option value="active">{{ __('Active') }}</option>
          <option value="inactive">{{ __('Inactive') }}</option>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">&nbsp;</label>
        <div>
          <button type="button" id="reset-filters" class="btn btn-label-secondary">
            <i class="bx bx-reset"></i> {{ __('Reset Filters') }}
          </button>
        </div>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table id="vendors-table" class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Company') }}</th>
            <th>{{ __('Email') }}</th>
            <th>{{ __('Phone') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Payment Terms') }}</th>
            <th>{{ __('Lead Time') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection