@extends('layouts.layoutMaster')

@section('title', __('Purchase Orders'))

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
        datatable: @json(route('wmsinventorycore.purchases.data')),
        show: @json(route('wmsinventorycore.purchases.show', ['purchase' => ':id'])),
        edit: @json(route('wmsinventorycore.purchases.edit', ['purchase' => ':id'])),
        destroy: @json(route('wmsinventorycore.purchases.destroy', ['purchase' => ':id'])),
        create: @json(route('wmsinventorycore.purchases.create')),
        approve: @json(route('wmsinventorycore.purchases.approve', ['purchase' => ':id'])),
        reject: @json(route('wmsinventorycore.purchases.reject', ['purchase' => ':id'])),
        receiveAll: @json(route('wmsinventorycore.purchases.receive', ['purchase' => ':id'])),
        partialReceive: @json(route('wmsinventorycore.purchases.receive-partial', ['purchase' => ':id'])),
        duplicate: @json(route('wmsinventorycore.purchases.duplicate', ['purchase' => ':id']))
      },
      labels: {
        // DataTable labels
        search: @json(__('Search')),
        lengthMenu: @json(__('_MENU_ entries per page')),
        info: @json(__('Showing _START_ to _END_ of _TOTAL_ entries')),
        previous: @json(__('Previous')),
        next: @json(__('Next')),
        
        // Column labels
        code: @json(__('PO Number')),
        date: @json(__('Date')),
        vendor: @json(__('Vendor')),
        warehouse: @json(__('Warehouse')),
        totalAmount: @json(__('Total Amount')),
        status: @json(__('Status')),
        approvalStatus: @json(__('Approval Status')),
        paymentStatus: @json(__('Payment Status')),
        items: @json(__('Items')),
        createdAt: @json(__('Created At')),
        actions: @json(__('Actions')),
        
        // Action labels
        confirmApprove: @json(__('Approve Purchase Order')),
        approveConfirmText: @json(__('Are you sure you want to approve this purchase order?')),
        approve: @json(__('Approve')),
        rejectPurchase: @json(__('Reject Purchase Order')),
        rejectConfirmText: @json(__('Are you sure you want to reject this purchase order?')),
        reject: @json(__('Reject')),
        rejectionReason: @json(__('Rejection Reason')),
        enterRejectionReason: @json(__('Please enter the reason for rejection')),
        rejectionReasonRequired: @json(__('Rejection reason is required')),
        receiveAll: @json(__('Receive All Items')),
        receiveAllConfirmText: @json(__('Are you sure you want to mark all items as received?')),
        duplicatePurchase: @json(__('Duplicate Purchase Order')),
        duplicateConfirmText: @json(__('Are you sure you want to duplicate this purchase order?')),
        duplicate: @json(__('Duplicate')),
        deletePurchase: @json(__('Delete Purchase Order')),
        deleteConfirmText: @json(__("You won't be able to revert this!")),
        delete: @json(__('Delete')),
        cancel: @json(__('Cancel')),
        
        // Success/Error messages
        purchaseCreatedSuccess: @json(__('Purchase order created successfully')),
        purchaseCreateError: @json(__('Failed to create purchase order')),
        approveError: @json(__('Failed to approve purchase order')),
        rejectError: @json(__('Failed to reject purchase order')),
        receiveError: @json(__('Failed to receive items')),
        deleteError: @json(__('Failed to delete purchase order')),
        
        // Form validation
        vendorRequired: @json(__('Please select a vendor')),
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
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-purchases.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Purchase Orders')"
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
          <option value="partially_received">{{ __('Partially Received') }}</option>
          <option value="received">{{ __('Received') }}</option>
          <option value="cancelled">{{ __('Cancelled') }}</option>
          <option value="rejected">{{ __('Rejected') }}</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">{{ __('Vendor') }}</label>
        <select id="vendorFilter" class="select2 form-select" data-placeholder="{{ __('Select Vendor') }}">
          <option value="">{{ __('All Vendors') }}</option>
          @foreach($vendors ?? [] as $vendor)
            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
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
      <h5 class="card-title mb-0">{{ __('Purchase Orders') }}</h5>
      @can('wmsinventory.create-purchase')
        <a href="{{ route('wmsinventorycore.purchases.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Create Purchase Order') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="card-datatable table-responsive">
      <table id="purchasesTable" class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('PO Number') }}</th>
            <th>{{ __('Vendor') }}</th>
            <th>{{ __('Warehouse') }}</th>
            <th>{{ __('PO Date') }}</th>
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