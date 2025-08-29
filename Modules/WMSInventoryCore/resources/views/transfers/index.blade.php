@extends('layouts.layoutMaster')

@section('title', __('Inventory Transfers'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        transfersData: @json(route('wmsinventorycore.transfers.data')),
        transfersApprove: @json(route('wmsinventorycore.transfers.approve', ['transfer' => '__TRANSFER_ID__'])),
        transfersShip: @json(route('wmsinventorycore.transfers.ship', ['transfer' => '__TRANSFER_ID__'])),
        transfersReceive: @json(route('wmsinventorycore.transfers.receive', ['transfer' => '__TRANSFER_ID__'])),
        transfersCancel: @json(route('wmsinventorycore.transfers.cancel', ['transfer' => '__TRANSFER_ID__'])),
        transfersDelete: @json(route('wmsinventorycore.transfers.destroy', ['transfer' => '__TRANSFER_ID__']))
      },
      labels: {
        confirmApprove: @json(__('Are you sure?')),
        confirmApproveText: @json(__('This will approve the transfer and update inventory levels!')),
        confirmApproveButton: @json(__('Yes, approve it!')),
        approved: @json(__('Approved!')),
        approvedText: @json(__('Transfer has been approved.')),
        shipTransfer: @json(__('Ship Transfer')),
        receiveTransfer: @json(__('Receive Transfer')),
        cancelTransfer: @json(__('Cancel Transfer')),
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Transfer has been deleted.')),
        shipped: @json(__('Shipped!')),
        shippedText: @json(__('Transfer has been shipped.')),
        received: @json(__('Received!')),
        receivedText: @json(__('Transfer has been received.')),
        cancelled: @json(__('Cancelled!')),
        cancelledText: @json(__('Transfer has been cancelled.')),
        error: @json(__('Error!'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-transfers.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Inventory Transfers')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Transfers') }}</h5>
      @can('wmsinventory.create-transfer')
        <a href="{{ route('wmsinventorycore.transfers.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Create Transfer') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="row mb-4">
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Source Warehouse') }}</label>
        <select id="source-warehouse-filter" class="form-select">
          <option value="">{{ __('All Warehouses') }}</option>
          @foreach($warehouses as $warehouse)
          <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Destination Warehouse') }}</label>
        <select id="destination-warehouse-filter" class="form-select">
          <option value="">{{ __('All Warehouses') }}</option>
          @foreach($warehouses as $warehouse)
          <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Status') }}</label>
        <select id="status-filter" class="form-select">
          <option value="">{{ __('All Statuses') }}</option>
          <option value="draft">{{ __('Draft') }}</option>
          <option value="approved">{{ __('Approved') }}</option>
          <option value="in_transit">{{ __('In Transit') }}</option>
          <option value="completed">{{ __('Completed') }}</option>
          <option value="cancelled">{{ __('Cancelled') }}</option>
        </select>
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">{{ __('Date Range') }}</label>
        <input type="text" id="date-range" class="form-control flatpickr-range">
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table class="table table-bordered datatables-transfers" id="transfers-table">
      <thead>
        <tr>
          <th>{{ __('ID') }}</th>
          <th>{{ __('Date') }}</th>
          <th>{{ __('Reference') }}</th>
          <th>{{ __('Source Warehouse') }}</th>
          <th>{{ __('Destination Warehouse') }}</th>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Actions') }}</th>
        </tr>
      </thead>
      </table>
    </div>
  </div>
</div>
@endsection
