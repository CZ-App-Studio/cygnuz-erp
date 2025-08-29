@extends('layouts.layoutMaster')

@section('title', __('Warehouse Details'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
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
      warehouse: @json($warehouse),
      urls: {
        warehousesIndex: @json(route('wmsinventorycore.warehouses.index')),
        warehousesEdit: @json(route('wmsinventorycore.warehouses.edit', $warehouse->id)),
        warehousesDelete: @json(route('wmsinventorycore.warehouses.destroy', $warehouse->id)),
        warehouseInventoryData: @json(route('wmsinventorycore.warehouse.inventory.data', $warehouse->id))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Warehouse has been deleted.')),
        error: @json(__('Error!')),
        couldNotDelete: @json(__('Could not delete warehouse.')),
        normal: @json(__('Normal')),
        lowStock: @json(__('Low Stock')),
        overstocked: @json(__('Overstocked'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-warehouse-details.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Warehouses'), 'url' => route('wmsinventorycore.warehouses.index')]
  ];
@endphp

<x-breadcrumb
  :title="$warehouse->name"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <!-- Warehouse Details Card -->
  <div class="col-xl-8 col-lg-7">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Warehouse Information') }}</h5>
        <div>
          <a href="{{ route('wmsinventorycore.warehouses.edit', $warehouse->id) }}" class="btn btn-primary btn-sm me-1">
            <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
          </a>
          <button class="btn btn-danger btn-sm delete-warehouse" data-id="{{ $warehouse->id }}">
            <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Warehouse Code') }}</h6>
              <p>{{ $warehouse->code }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Contact Person') }}</h6>
              <p>{{ $warehouse->contact_name ?: __('N/A') }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Status') }}</h6>
              <p>
                @if($warehouse->is_active)
                <span class="badge bg-success">{{ __('Active') }}</span>
                @else
                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                @endif
              </p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Contact Info') }}</h6>
              <p>
                @if($warehouse->contact_email || $warehouse->contact_phone)
                  {{ $warehouse->contact_email }}<br>
                  {{ $warehouse->contact_phone }}
                @else
                  {{ __('N/A') }}
                @endif
              </p>
            </div>
          </div>
        </div>
        
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Address') }}</h6>
          <p>{{ $warehouse->address }}</p>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Warehouse Summary Card -->
  <div class="col-xl-4 col-lg-5">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Inventory Summary') }}</h5>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Total Products') }}</h6>
            <p class="mb-0 text-muted">{{ __('Unique SKUs') }}</p>
          </div>
          <h4>{{ $totalProducts }}</h4>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Total Stock Value') }}</h6>
            <p class="mb-0 text-muted">{{ __('Based on Cost Price') }}</p>
          </div>
          <h4>{{ $totalStockValue }}</h4>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">{{ __('Low Stock Items') }}</h6>
            <p class="mb-0 text-muted">{{ __('Below Threshold') }}</p>
          </div>
          <h4>{{ $lowStockCount }}</h4>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Warehouse Zones Card -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Warehouse Zones') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('Zone Name') }}</th>
            <th>{{ __('Zone Code') }}</th>
            <th>{{ __('Bin Locations') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($warehouse->zones as $zone)
          <tr>
            <td>{{ $zone->name }}</td>
            <td>{{ $zone->code }}</td>
            <td>{{ $zone->binLocations->count() ?? 0 }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="3" class="text-center">{{ __('No zones defined') }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Product Stock Card -->
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Product Stock') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover border-top datatable-warehouse-inventory">
        <thead>
          <tr>
            <th>{{ __('Product Code') }}</th>
            <th>{{ __('Product Name') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Stock Level') }}</th>
            <th>{{ __('Min Stock') }}</th>
            <th>{{ __('Max Stock') }}</th>
            <th>{{ __('Status') }}</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>
@endsection
