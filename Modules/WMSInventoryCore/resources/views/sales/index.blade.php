@extends('layouts.layoutMaster')

@section('title', __('Sales Orders'))

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
        datatable: @json(route('wmsinventorycore.sales.data')),
        show: @json(route('wmsinventorycore.sales.show', ['sale' => ':id'])),
        edit: @json(route('wmsinventorycore.sales.edit', ['sale' => ':id'])),
        destroy: @json(route('wmsinventorycore.sales.destroy', ['sale' => ':id'])),
        create: @json(route('wmsinventorycore.sales.create')),
        approve: @json(route('wmsinventorycore.sales.approve', ['sale' => ':id'])),
        reject: @json(route('wmsinventorycore.sales.reject', ['sale' => ':id'])),
        fulfillAll: @json(route('wmsinventorycore.sales.fulfill', ['sale' => ':id'])),
        partialFulfill: @json(route('wmsinventorycore.sales.fulfill-partial', ['sale' => ':id'])),
        duplicate: @json(route('wmsinventorycore.sales.duplicate', ['sale' => ':id'])),
        deliver: @json(route('wmsinventorycore.sales.deliver', ['sale' => ':id']))
      },
      labels: {
        // DataTable labels
        search: @json(__('Search')),
        lengthMenu: @json(__('_MENU_ entries per page')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        previous: @json(__('Previous')),
        next: @json(__('Next')),
        
        // Column labels
        code: @json(__('SO Number')),
        date: @json(__('Date')),
        customer: @json(__('Customer')),
        warehouse: @json(__('Warehouse')),
        totalAmount: @json(__('Total Amount')),
        status: @json(__('Status')),
        approvalStatus: @json(__('Approval Status')),
        paymentStatus: @json(__('Payment Status')),
        items: @json(__('Items')),
        createdAt: @json(__('Created At')),
        actions: @json(__('Actions')),
        
        // Action labels
        confirmApprove: @json(__('Approve Sales Order')),
        approveConfirmText: @json(__('Are you sure you want to approve this sales order?')),
        approve: @json(__('Approve')),
        rejectSale: @json(__('Reject Sales Order')),
        rejectConfirmText: @json(__('Are you sure you want to reject this sales order?')),
        reject: @json(__('Reject')),
        rejectionReason: @json(__('Rejection Reason')),
        enterRejectionReason: @json(__('Please enter the reason for rejection')),
        rejectionReasonRequired: @json(__('Rejection reason is required')),
        fulfillAll: @json(__('Fulfill All Items')),
        fulfillAllConfirmText: @json(__('Are you sure you want to mark all items as fulfilled?')),
        duplicateSale: @json(__('Duplicate Sales Order')),
        duplicateConfirmText: @json(__('Are you sure you want to duplicate this sales order?')),
        duplicate: @json(__('Duplicate')),
        deleteSale: @json(__('Delete Sales Order')),
        deleteConfirmText: @json(__("You won't be able to revert this!")),
        delete: @json(__('Delete')),
        cancel: @json(__('Cancel')),
        
        // Success/Error messages
        saleCreatedSuccess: @json(__('Sales order created successfully')),
        saleCreateError: @json(__('Failed to create sales order')),
        approveError: @json(__('Failed to approve sales order')),
        rejectError: @json(__('Failed to reject sales order')),
        fulfillError: @json(__('Failed to fulfill items')),
        deleteError: @json(__('Failed to delete sales order')),
        
        // Form validation
        customerRequired: @json(__('Please select a customer')),
        warehouseRequired: @json(__('Please select a warehouse')),
        dateRequired: @json(__('Please select a date')),
        productsRequired: @json(__('Please add at least one product')),
        validProductsRequired: @json(__('Please ensure all product rows have valid data')),
        
        // Product repeater
        selectProduct: @json(__('Select Product')),
        unit: @json(__('Unit')),
        confirmRemoveProduct: @json(__('Are you sure you want to remove this product?'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-sales.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Sales Orders')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

{{-- Filters Card --}}
<div class="card mb-4">
  <div class="card-header">
    <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3">
        <label class="form-label">{{ __('Status') }}</label>
        <select id="statusFilter" class="select2 form-select" data-placeholder="{{ __('Select Status') }}">
          <option value="">{{ __('All Statuses') }}</option>
          <option value="draft">{{ __('Draft') }}</option>
          <option value="pending">{{ __('Pending Approval') }}</option>
          <option value="approved">{{ __('Approved') }}</option>
          <option value="partially_fulfilled">{{ __('Partially Fulfilled') }}</option>
          <option value="fulfilled">{{ __('Fulfilled') }}</option>
          <option value="shipped">{{ __('Shipped') }}</option>
          <option value="delivered">{{ __('Delivered') }}</option>
          <option value="cancelled">{{ __('Cancelled') }}</option>
          <option value="rejected">{{ __('Rejected') }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Customer') }}</label>
        <select id="customerFilter" class="select2 form-select" data-placeholder="{{ __('Select Customer') }}">
          <option value="">{{ __('All Customers') }}</option>
          @foreach($customers ?? [] as $customer)
            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Warehouse') }}</label>
        <select id="warehouseFilter" class="select2 form-select" data-placeholder="{{ __('Select Warehouse') }}">
          <option value="">{{ __('All Warehouses') }}</option>
          @foreach($warehouses ?? [] as $warehouse)
            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">&nbsp;</label>
        <div>
          <button type="button" id="clearFilters" class="btn btn-label-secondary w-100">
            <i class="bx bx-reset"></i> {{ __('Reset Filters') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Data Table Card --}}
<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('Sales Orders') }}</h5>
      @can('wmsinventory.create-sale')
        <a href="{{ route('wmsinventorycore.sales.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Create Sales Order') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="card-datatable table-responsive">
      <table id="salesTable" class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('SO Number') }}</th>
            <th>{{ __('Customer') }}</th>
            <th>{{ __('Warehouse') }}</th>
            <th>{{ __('Sale Date') }}</th>
            <th>{{ __('Expected Delivery') }}</th>
            <th>{{ __('Total Amount') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Payment Status') }}</th>
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