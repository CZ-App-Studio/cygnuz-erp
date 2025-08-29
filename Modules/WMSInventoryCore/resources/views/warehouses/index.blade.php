@extends('layouts.layoutMaster')

@section('title', __('Warehouses'))

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
        warehousesData: @json(route('wmsinventorycore.warehouses.data')),
        warehousesShow: @json(route('wmsinventorycore.warehouses.show', ['warehouse' => '__WAREHOUSE_ID__'])),
        warehousesEdit: @json(route('wmsinventorycore.warehouses.edit', ['warehouse' => '__WAREHOUSE_ID__'])),
        warehousesDelete: @json(route('wmsinventorycore.warehouses.destroy', ['warehouse' => '__WAREHOUSE_ID__'])),
        warehousesCreate: @json(route('wmsinventorycore.warehouses.create'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-warehouses.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Warehouses')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Warehouses') }}</h5>
      @can('wmsinventory.create-warehouse')
        <a href="{{ route('wmsinventorycore.warehouses.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Add New Warehouse') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table table-bordered datatables-warehouses">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('Code') }}</th>
            <th>{{ __('Address') }}</th>
            <th>{{ __('Contact Person') }}</th>
            <th>{{ __('Contact Email') }}</th>
            <th>{{ __('Contact Phone') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
      </table>
    </div>
</div>

<!-- Warehouse Details Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasWarehouseDetails" aria-labelledby="offcanvasWarehouseDetailsLabel">
  <div class="offcanvas-header">
    <h5 id="offcanvasWarehouseDetailsLabel">{{ __('Warehouse Details') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
  </div>
  <div class="offcanvas-body">
    <div id="warehouse-details-content">
      <!-- Content will be loaded dynamically -->
    </div>
  </div>
</div>
@endsection
