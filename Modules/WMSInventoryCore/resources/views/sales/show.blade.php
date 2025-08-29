@extends('layouts.layoutMaster')

@section('title', __('Sales Order Details') . ' - ' . $sale->code)

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      saleId: {{ $sale->id }},
      urls: {
        approve: @json(route('wmsinventorycore.sales.approve', $sale->id)),
        reject: @json(route('wmsinventorycore.sales.reject', $sale->id)),
        fulfill: @json(route('wmsinventorycore.sales.fulfill', $sale->id)),
        fulfillAll: @json(route('wmsinventorycore.sales.fulfill', $sale->id)),
        duplicate: @json(route('wmsinventorycore.sales.duplicate', $sale->id)),
        deliver: @json(route('wmsinventorycore.sales.deliver', $sale->id)),
        ship: @json(route('wmsinventorycore.sales.ship', $sale->id)),
        printPdf: @json(route('wmsinventorycore.sales.pdf', $sale->id)),
        print: @json(route('wmsinventorycore.sales.pdf', $sale->id)),
        downloadPdf: @json(route('wmsinventorycore.sales.pdf', $sale->id)),
        delete: @json(route('wmsinventorycore.sales.destroy', $sale->id)),
        index: @json(route('wmsinventorycore.sales.index')),
        show: @json(route('wmsinventorycore.sales.show', ['sale' => ':id']))
      },
      labels: {
        confirmApprove: @json(__('Approve Sales Order')),
        confirmApproveText: @json(__('Are you sure you want to approve this sales order?')),
        approved: @json(__('Approved!')),
        approvedText: @json(__('Sales order has been approved.')),
        confirmReject: @json(__('Reject Sales Order')),
        confirmRejectText: @json(__('Are you sure you want to reject this sales order?')),
        rejected: @json(__('Rejected!')),
        rejectedText: @json(__('Sales order has been rejected.')),
        confirmDelete: @json(__('Delete Sales Order')),
        confirmDeleteText: @json(__('Are you sure you want to delete this sales order? This action cannot be undone.')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Sales order has been deleted.')),
        confirmFulfill: @json(__('Fulfill Order')),
        confirmFulfillText: @json(__('Are you sure you want to fulfill this order?')),
        confirmFulfillAll: @json(__('Fulfill All Items')),
        confirmFulfillAllText: @json(__('Are you sure you want to fulfill all items in this order?')),
        fulfilled: @json(__('Fulfilled!')),
        fulfilledText: @json(__('Sales order has been fulfilled.')),
        duplicateSale: @json(__('Duplicate Sales Order')),
        duplicateConfirmText: @json(__('Are you sure you want to duplicate this sales order?')),
        duplicate: @json(__('Duplicate')),
        duplicateSuccess: @json(__('Success!')),
        confirmDeliver: @json(__('Mark as Delivered')),
        deliverConfirmText: @json(__('Are you sure you want to mark this sales order as delivered?')),
        deliver: @json(__('Mark Delivered')),
        deliveredSuccess: @json(__('Delivered!')),
        confirmShip: @json(__('Ship Order')),
        shipConfirmText: @json(__('Are you sure you want to mark this sales order as shipped?')),
        ship: @json(__('Ship Order')),
        shippedSuccess: @json(__('Shipped!')),
        confirmDuplicate: @json(__('Duplicate Sales Order')),
        confirmDuplicateText: @json(__('Are you sure you want to duplicate this sales order?')),
        cancel: @json(__('Cancel')),
        ok: @json(__('OK')),
        error: @json(__('Error!'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-sale-show.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Sales Orders'), 'url' => route('wmsinventorycore.sales.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Sales Order Details') . ' - ' . $sale->code"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-12">
    <!-- Sales Order Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('Sales Order') }} #{{ $sale->code }}</h5>
        <div class="d-flex gap-2">
          @can('wmsinventory.approve-sale')
            @if($sale->status === 'pending')
              <button type="button" class="btn btn-success btn-sm" onclick="approveSale()">
                <i class="bx bx-check"></i> {{ __('Approve') }}
              </button>
              <button type="button" class="btn btn-danger btn-sm" onclick="rejectSale()">
                <i class="bx bx-x"></i> {{ __('Reject') }}
              </button>
            @endif
          @endcan
          
          @can('wmsinventory.fulfill-sale')
            @if(in_array($sale->status, ['approved', 'partially_fulfilled']))
              <a href="{{ route('wmsinventorycore.sales.show-fulfill', $sale->id) }}" class="btn btn-info btn-sm">
                <i class="bx bx-package"></i> {{ __('Fulfill Items') }}
              </a>
              <button type="button" class="btn btn-success btn-sm" onclick="fulfillAllSale()">
                <i class="bx bx-check-double"></i> {{ __('Fulfill All') }}
              </button>
            @endif
          @endcan

          @can('wmsinventory.ship-sale')
            @if($sale->status === 'fulfilled')
              <button type="button" class="btn btn-primary btn-sm" onclick="shipSale()">
                <i class="bx bx-rocket"></i> {{ __('Ship Order') }}
              </button>
            @endif
          @endcan

          @can('wmsinventory.deliver-sale')
            @if($sale->status === 'shipped')
              <button type="button" class="btn btn-success btn-sm" onclick="deliverSale()">
                <i class="bx bx-check-circle"></i> {{ __('Mark Delivered') }}
              </button>
            @endif
          @endcan

          @can('wmsinventory.edit-sale')
            @if(in_array($sale->status, ['draft', 'pending']))
              <a href="{{ route('wmsinventorycore.sales.edit', $sale->id) }}" class="btn btn-primary btn-sm">
                <i class="bx bx-edit"></i> {{ __('Edit') }}
              </a>
            @endif
          @endcan

          @can('wmsinventory.duplicate-sale')
            <button type="button" class="btn btn-info btn-sm" onclick="duplicateSale()">
              <i class="bx bx-copy"></i> {{ __('Duplicate') }}
            </button>
          @endcan

          <a href="{{ route('wmsinventorycore.sales.pdf', $sale->id) }}" class="btn btn-secondary btn-sm" target="_blank">
            <i class="bx bx-printer"></i> {{ __('Print PDF') }}
          </a>

          @can('wmsinventory.delete-sale')
            @if($sale->status === 'draft')
              <button type="button" class="btn btn-label-danger btn-sm" onclick="deleteSale()">
                <i class="bx bx-trash"></i> {{ __('Delete') }}
              </button>
            @endif
          @endcan
        </div>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Status') }}:</strong></div>
              <div class="col-sm-8">
                @switch($sale->status)
                  @case('draft')
                    <span class="badge bg-secondary">{{ __('Draft') }}</span>
                    @break
                  @case('pending')
                    <span class="badge bg-warning">{{ __('Pending Approval') }}</span>
                    @break
                  @case('approved')
                    <span class="badge bg-success">{{ __('Approved') }}</span>
                    @break
                  @case('partially_fulfilled')
                    <span class="badge bg-info">{{ __('Partially Fulfilled') }}</span>
                    @break
                  @case('fulfilled')
                    <span class="badge bg-primary">{{ __('Fulfilled') }}</span>
                    @break
                  @case('shipped')
                    <span class="badge bg-info">{{ __('Shipped') }}</span>
                    @break
                  @case('delivered')
                    <span class="badge bg-success">{{ __('Delivered') }}</span>
                    @break
                  @case('cancelled')
                    <span class="badge bg-dark">{{ __('Cancelled') }}</span>
                    @break
                  @case('rejected')
                    <span class="badge bg-danger">{{ __('Rejected') }}</span>
                    @break
                @endswitch
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Sale Date') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->date ? $sale->date->format('F j, Y') : '-' }}</div>
            </div>
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Expected Delivery') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->expected_delivery_date ? $sale->expected_delivery_date->format('F j, Y') : '-' }}</div>
            </div>
            @if($sale->actual_delivery_date)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Actual Delivery') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->actual_delivery_date->format('F j, Y') }}</div>
            </div>
            @endif
          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Customer') }}:</strong></div>
              <div class="col-sm-8">
                <strong>{{ $sale->customer->name }}</strong><br>
                @if($sale->customer->company_name)
                  {{ $sale->customer->company_name }}<br>
                @endif
                {{ $sale->customer->email }}<br>
                {{ $sale->customer->phone_number }}
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Warehouse') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->warehouse->name }}</div>
            </div>
            @if($sale->approved_by_id)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Approved By') }}:</strong></div>
              <div class="col-sm-8">
                {{ $sale->approvedBy ? $sale->approvedBy->name : '-' }}
                @if($sale->approved_at)
                  <br><small class="text-muted">{{ $sale->approved_at->format('F j, Y g:i A') }}</small>
                @endif
              </div>
            </div>
            @endif
            @if($sale->fulfilled_by_id)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Fulfilled By') }}:</strong></div>
              <div class="col-sm-8">
                {{ $sale->fulfilledBy ? $sale->fulfilledBy->name : '-' }}
                @if($sale->fulfilled_at)
                  <br><small class="text-muted">{{ $sale->fulfilled_at->format('F j, Y g:i A') }}</small>
                @endif
              </div>
            </div>
            @endif
          </div>
        </div>

        <hr>
        <div class="row">
          <div class="col-md-6">
            @if($sale->payment_terms)
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Payment Terms') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->payment_terms }}</div>
            </div>
            @endif
            @if($sale->payment_due_date)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Payment Due') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->payment_due_date->format('F j, Y') }}</div>
            </div>
            @endif
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Payment Status') }}:</strong></div>
              <div class="col-sm-8">
                @switch($sale->payment_status)
                  @case('unpaid')
                    <span class="badge bg-danger">{{ __('Unpaid') }}</span>
                    @break
                  @case('partial')
                    <span class="badge bg-warning">{{ __('Partially Paid') }}</span>
                    @break
                  @case('paid')
                    <span class="badge bg-success">{{ __('Paid') }}</span>
                    @break
                  @default
                    <span class="badge bg-secondary">{{ __('Unknown') }}</span>
                @endswitch
                @if($sale->paid_amount > 0)
                  <small class="ms-2">({{ __('Paid') }}: ${{ number_format($sale->paid_amount, 2) }})</small>
                @endif
              </div>
            </div>
          </div>
          <div class="col-md-6">
            @if($sale->reference_no)
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Reference #') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->reference_no }}</div>
            </div>
            @endif
            @if($sale->invoice_no)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Invoice #') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->invoice_no }}</div>
            </div>
            @endif
            @if($sale->tracking_number)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Tracking #') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->tracking_number }}</div>
            </div>
            @endif
            @if($sale->shipping_method)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Shipping Method') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->shipping_method }}</div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Sales Order Items -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Order Items') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('SKU') }}</th>
                <th class="text-end">{{ __('Quantity') }}</th>
                <th class="text-end">{{ __('Unit Price') }}</th>
                <th class="text-end">{{ __('Total') }}</th>
                @if($sale->products && $sale->products->some(fn($item) => $item->fulfilled_quantity > 0))
                  <th class="text-end">{{ __('Fulfilled') }}</th>
                  <th class="text-end">{{ __('Remaining') }}</th>
                @endif
                @if($sale->products && $sale->products->some(fn($item) => $item->notes))
                  <th>{{ __('Notes') }}</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse($sale->products ?? [] as $item)
              <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->sku }}</td>
                <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                @if($sale->products && $sale->products->some(fn($item) => $item->fulfilled_quantity > 0))
                  <td class="text-end">{{ number_format($item->fulfilled_quantity, 2) }}</td>
                  <td class="text-end">{{ number_format($item->quantity - $item->fulfilled_quantity, 2) }}</td>
                @endif
                @if($sale->products && $sale->products->some(fn($item) => $item->notes))
                  <td>{{ $item->notes ?: '-' }}</td>
                @endif
              </tr>
              @empty
              <tr>
                <td colspan="10" class="text-center">{{ __('No items found') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Totals and Additional Information -->
    <div class="row">
      <div class="col-md-8">
        @if($sale->notes || $sale->terms_conditions)
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Additional Information') }}</h5>
          </div>
          <div class="card-body">
            @if($sale->notes)
            <div class="mb-3">
              <h6>{{ __('Notes') }}</h6>
              <p class="text-muted">{{ $sale->notes }}</p>
            </div>
            @endif

            @if($sale->terms_conditions)
            <div class="mb-3">
              <h6>{{ __('Terms & Conditions') }}</h6>
              <p class="text-muted">{{ $sale->terms_conditions }}</p>
            </div>
            @endif
          </div>
        </div>
        @endif

        @if($sale->status_history && $sale->status_history->count() > 0)
        <div class="card mt-4">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Status History') }}</h5>
          </div>
          <div class="card-body">
            <div class="timeline">
              @foreach($sale->status_history ? $sale->status_history->sortByDesc('created_at') : [] as $history)
              <div class="timeline-item">
                <div class="timeline-marker">
                  <div class="timeline-marker-indicator bg-primary"></div>
                </div>
                <div class="timeline-content">
                  <div class="timeline-header">
                    <span class="badge bg-primary">{{ ucfirst($history->status) }}</span>
                    <small class="text-muted">{{ $history->created_at->format('M j, Y g:i A') }}</small>
                  </div>
                  @if($history->notes)
                  <div class="timeline-body">
                    {{ $history->notes }}
                  </div>
                  @endif
                  <div class="timeline-footer">
                    <small class="text-muted">{{ __('by') }} {{ $history->user->name ?? 'System' }}</small>
                  </div>
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Order Summary') }}</h5>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Subtotal') }}:</span>
              <span>${{ number_format($sale->subtotal, 2) }}</span>
            </div>

            @if($sale->discount_amount > 0)
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Discount') }}:</span>
              <span class="text-danger">-${{ number_format($sale->discount_amount, 2) }}</span>
            </div>
            @endif

            @if($sale->tax_amount > 0)
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Tax') }}:</span>
              <span>${{ number_format($sale->tax_amount, 2) }}</span>
            </div>
            @endif

            @if($sale->shipping_cost > 0)
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Shipping Cost') }}:</span>
              <span>${{ number_format($sale->shipping_cost, 2) }}</span>
            </div>
            @endif

            <hr>

            <div class="d-flex justify-content-between">
              <strong>{{ __('Total Amount') }}:</strong>
              <strong>${{ number_format($sale->total_amount, 2) }}</strong>
            </div>

            @if($sale->payment_status)
            <hr>
            <div class="d-flex justify-content-between">
              <span>{{ __('Payment Status') }}:</span>
              <span>
                @switch($sale->payment_status)
                  @case('pending')
                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                    @break
                  @case('partial')
                    <span class="badge bg-info">{{ __('Partial') }}</span>
                    @break
                  @case('paid')
                    <span class="badge bg-success">{{ __('Paid') }}</span>
                    @break
                  @case('overdue')
                    <span class="badge bg-danger">{{ __('Overdue') }}</span>
                    @break
                @endswitch
              </span>
            </div>
            @endif
          </div>
        </div>

        @if($sale->created_by || $sale->updated_by)
        <div class="card mt-4">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Audit Trail') }}</h5>
          </div>
          <div class="card-body">
            @if($sale->created_by)
            <div class="mb-2">
              <strong>{{ __('Created by') }}:</strong><br>
              <small class="text-muted">
                {{ $sale->createdBy->name ?? 'Unknown' }}<br>
                {{ $sale->created_at->format('M j, Y g:i A') }}
              </small>
            </div>
            @endif

            @if($sale->updated_by && $sale->updated_at != $sale->created_at)
            <div class="mb-2">
              <strong>{{ __('Last updated by') }}:</strong><br>
              <small class="text-muted">
                {{ $sale->updatedBy->name ?? 'Unknown' }}<br>
                {{ $sale->updated_at->format('M j, Y g:i A') }}
              </small>
            </div>
            @endif
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

<style>
.timeline {
  position: relative;
  padding-left: 1.5rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e3e6f0;
}

.timeline-item {
  position: relative;
  padding-bottom: 1rem;
}

.timeline-marker {
  position: absolute;
  left: -1rem;
  top: 0.25rem;
}

.timeline-marker-indicator {
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  border: 2px solid #fff;
}

.timeline-content {
  margin-left: 1rem;
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.25rem;
}

.timeline-body {
  margin-bottom: 0.25rem;
}

.timeline-footer {
  font-size: 0.875rem;
}
</style>
@endsection