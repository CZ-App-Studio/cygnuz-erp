@extends('layouts.layoutMaster')

@section('title', __('Inventory Adjustments'))

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
        adjustmentsData: @json(route('wmsinventorycore.adjustments.data')),
        adjustmentsCreate: @json(route('wmsinventorycore.adjustments.create')),
        adjustmentsShow: @json(route('wmsinventorycore.adjustments.show', ['adjustment' => '__ADJUSTMENT_ID__'])),
        adjustmentsEdit: @json(route('wmsinventorycore.adjustments.edit', ['adjustment' => '__ADJUSTMENT_ID__'])),
        adjustmentsDelete: @json(route('wmsinventorycore.adjustments.destroy', ['adjustment' => '__ADJUSTMENT_ID__'])),
        adjustmentsApprove: @json(route('wmsinventorycore.adjustments.approve', ['adjustment' => '__ADJUSTMENT_ID__']))
      },
      filters: {
        warehouses: @json($warehouses),
        adjustmentTypes: @json($adjustmentTypes)
      },
      labels: {
        confirmApprove: @json(__('Are you sure?')),
        confirmApproveText: @json(__('This will approve the adjustment and update inventory levels!')),
        confirmApproveButton: @json(__('Yes, approve it!')),
        approved: @json(__('Approved!')),
        approvedText: @json(__('Adjustment has been approved.')),
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Adjustment has been deleted.')),
        error: @json(__('Error!'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-adjustments.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Inventory Adjustments')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Adjustments') }}</h5>
      @can('wmsinventory.create-adjustment')
        <a href="{{ route('wmsinventorycore.adjustments.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Create Adjustment') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="row mb-4">
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Filter by Warehouse') }}</label>
        <select id="warehouse-filter" class="form-select">
          <option value="">{{ __('All Warehouses') }}</option>
          @foreach($warehouses as $warehouse)
          <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Filter by Type') }}</label>
        <select id="type-filter" class="form-select">
          <option value="">{{ __('All Types') }}</option>
          @foreach($adjustmentTypes as $type)
          <option value="{{ $type->id }}">{{ $type->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Date From') }}</label>
        <input type="date" id="date-from" class="form-control">
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Date To') }}</label>
        <input type="date" id="date-to" class="form-control">
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="table table-bordered datatables-adjustments" id="adjustments-table">
      <thead>
        <tr>
          <th>{{ __('ID') }}</th>
          <th>{{ __('Date') }}</th>
          <th>{{ __('Code') }}</th>
          <th>{{ __('Warehouse') }}</th>
          <th>{{ __('Type') }}</th>
          <th>{{ __('Total') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
      </thead>
    </table>
    </div>
  </div>
</div>
@endsection
