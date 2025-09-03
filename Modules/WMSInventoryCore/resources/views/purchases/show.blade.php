@extends('layouts.layoutMaster')

@section('title', __('Purchase Order Details') . ' - ' . $purchase->code)

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
      urls: {
        approve: @json(route('wmsinventorycore.purchases.approve', $purchase->id)),
        reject: @json(route('wmsinventorycore.purchases.reject', $purchase->id)),
        receive: @json(route('wmsinventorycore.purchases.receive', $purchase->id)),
        printPdf: @json(route('wmsinventorycore.purchases.pdf', $purchase->id)),
        delete: @json(route('wmsinventorycore.purchases.destroy', $purchase->id)),
        updatePaymentStatus: @json(route('wmsinventorycore.purchases.update-payment-status', $purchase->id)),
        index: @json(route('wmsinventorycore.purchases.index'))
      },
      labels: {
        confirmApprove: @json(__('Approve Purchase Order')),
        confirmApproveText: @json(__('Are you sure you want to approve this purchase order?')),
        approved: @json(__('Approved!')),
        approvedText: @json(__('Purchase order has been approved.')),
        confirmReject: @json(__('Reject Purchase Order')),
        confirmRejectText: @json(__('Are you sure you want to reject this purchase order?')),
        rejected: @json(__('Rejected!')),
        rejectedText: @json(__('Purchase order has been rejected.')),
        confirmDelete: @json(__('Delete Purchase Order')),
        confirmDeleteText: @json(__('Are you sure you want to delete this purchase order? This action cannot be undone.')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Purchase order has been deleted.')),
        error: @json(__('Error!'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-purchase-show.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Purchase Orders'), 'url' => route('wmsinventorycore.purchases.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Purchase Order Details') . ' - ' . $purchase->code"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-12">
    <!-- Purchase Order Header -->
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">{{ __('Purchase Order') }} #{{ $purchase->code }}</h5>
        <div class="d-flex gap-2">
          @can('wmsinventory.approve-purchase')
            @if($purchase->status === 'pending')
              <button type="button" class="btn btn-success btn-sm" onclick="approvePurchase()">
                <i class="bx bx-check"></i> {{ __('Approve') }}
              </button>
              <button type="button" class="btn btn-danger btn-sm" onclick="rejectPurchase()">
                <i class="bx bx-x"></i> {{ __('Reject') }}
              </button>
            @endif
          @endcan
          
          @can('wmsinventory.receive-purchase')
            @if(in_array($purchase->status, ['approved', 'partially_received']))
              <a href="{{ route('wmsinventorycore.purchases.show-receive', $purchase->id) }}" class="btn btn-info btn-sm">
                <i class="bx bx-package"></i> {{ __('Receive Items') }}
              </a>
            @endif
          @endcan

          @can('wmsinventory.edit-purchase')
            @if(in_array($purchase->status, ['draft', 'pending']))
              <a href="{{ route('wmsinventorycore.purchases.edit', $purchase->id) }}" class="btn btn-primary btn-sm">
                <i class="bx bx-edit"></i> {{ __('Edit') }}
              </a>
            @endif
          @endcan

          <a href="{{ route('wmsinventorycore.purchases.pdf', $purchase->id) }}" class="btn btn-secondary btn-sm" target="_blank">
            <i class="bx bx-printer"></i> {{ __('Print PDF') }}
          </a>

          @can('wmsinventory.delete-purchase')
            @if($purchase->status === 'draft')
              <button type="button" class="btn btn-label-danger btn-sm" onclick="deletePurchase()">
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
                @switch($purchase->status)
                  @case('draft')
                    <span class="badge bg-secondary">{{ __('Draft') }}</span>
                    @break
                  @case('pending')
                    <span class="badge bg-warning">{{ __('Pending Approval') }}</span>
                    @break
                  @case('approved')
                    <span class="badge bg-success">{{ __('Approved') }}</span>
                    @break
                  @case('partially_received')
                    <span class="badge bg-info">{{ __('Partially Received') }}</span>
                    @break
                  @case('received')
                    <span class="badge bg-primary">{{ __('Received') }}</span>
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
              <div class="col-sm-4"><strong>{{ __('PO Date') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->date ? $purchase->date->format('F j, Y') : '-' }}</div>
            </div>
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Expected Delivery') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->expected_delivery_date ? $purchase->expected_delivery_date->format('F j, Y') : '-' }}</div>
            </div>
            @if($purchase->actual_delivery_date)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Actual Delivery') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->actual_delivery_date->format('F j, Y') }}</div>
            </div>
            @endif
          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Vendor') }}:</strong></div>
              <div class="col-sm-8">
                <strong>{{ $purchase->vendor->name }}</strong><br>
                @if($purchase->vendor->company_name)
                  {{ $purchase->vendor->company_name }}<br>
                @endif
                {{ $purchase->vendor->email }}<br>
                {{ $purchase->vendor->phone_number }}
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Warehouse') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->warehouse->name }}</div>
            </div>
            @if($purchase->approved_by_id)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Approved By') }}:</strong></div>
              <div class="col-sm-8">
                {{ $purchase->approvedBy ? $purchase->approvedBy->name : '-' }}
                @if($purchase->approved_at)
                  <br><small class="text-muted">{{ $purchase->approved_at->format('F j, Y g:i A') }}</small>
                @endif
              </div>
            </div>
            @endif
            @if($purchase->received_by_id)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Received By') }}:</strong></div>
              <div class="col-sm-8">
                {{ $purchase->receivedBy ? $purchase->receivedBy->name : '-' }}
                @if($purchase->received_at)
                  <br><small class="text-muted">{{ $purchase->received_at->format('F j, Y g:i A') }}</small>
                @endif
              </div>
            </div>
            @endif
          </div>
        </div>

        <hr>
        <div class="row">
          <div class="col-md-6">
            @if($purchase->payment_terms)
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Payment Terms') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->payment_terms }}</div>
            </div>
            @endif
            @if($purchase->payment_due_date)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Payment Due') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->payment_due_date->format('F j, Y') }}</div>
            </div>
            @endif
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Payment Status') }}:</strong></div>
              <div class="col-sm-8">
                <div class="d-flex align-items-center gap-2">
                  @switch($purchase->payment_status)
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
                  @can('wmsinventory.edit-purchase')
                    @if(in_array($purchase->status, ['approved', 'partially_received', 'received']))
                      <button type="button" class="btn btn-sm btn-outline-primary" onclick="updatePaymentStatus()">
                        <i class="bx bx-edit"></i> {{ __('Update') }}
                      </button>
                    @endif
                  @endcan
                </div>
                @if($purchase->paid_amount > 0)
                  <small class="ms-0 mt-1 d-block">({{ __('Paid') }}: ${{ number_format($purchase->paid_amount, 2) }} / ${{ number_format($purchase->total_amount, 2) }})</small>
                @endif
              </div>
            </div>
          </div>
          <div class="col-md-6">
            @if($purchase->reference_no)
            <div class="row">
              <div class="col-sm-4"><strong>{{ __('Reference #') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->reference_no }}</div>
            </div>
            @endif
            @if($purchase->invoice_no)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Invoice #') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->invoice_no }}</div>
            </div>
            @endif
            @if($purchase->tracking_number)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Tracking #') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->tracking_number }}</div>
            </div>
            @endif
            @if($purchase->shipping_method)
            <div class="row mt-2">
              <div class="col-sm-4"><strong>{{ __('Shipping Method') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->shipping_method }}</div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Purchase Order Items -->
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
                @if($purchase->products && $purchase->products->some(fn($item) => $item->received_quantity > 0))
                  <th class="text-end">{{ __('Received') }}</th>
                  <th class="text-end">{{ __('Remaining') }}</th>
                @endif
                @if($purchase->products && $purchase->products->some(fn($item) => $item->notes))
                  <th>{{ __('Notes') }}</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @forelse($purchase->products ?? [] as $item)
              <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->sku }}</td>
                <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-end">${{ number_format($item->unit_cost, 2) }}</td>
                <td class="text-end">${{ number_format($item->subtotal, 2) }}</td>
                @if($purchase->products && $purchase->products->some(fn($item) => $item->received_quantity > 0))
                  <td class="text-end">{{ number_format($item->received_quantity, 2) }}</td>
                  <td class="text-end">{{ number_format($item->quantity - $item->received_quantity, 2) }}</td>
                @endif
                @if($purchase->products && $purchase->products->some(fn($item) => $item->notes))
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
        @if($purchase->notes || $purchase->terms_conditions)
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Additional Information') }}</h5>
          </div>
          <div class="card-body">
            @if($purchase->notes)
            <div class="mb-3">
              <h6>{{ __('Notes') }}</h6>
              <p class="text-muted">{{ $purchase->notes }}</p>
            </div>
            @endif

            @if($purchase->terms_conditions)
            <div class="mb-3">
              <h6>{{ __('Terms & Conditions') }}</h6>
              <p class="text-muted">{{ $purchase->terms_conditions }}</p>
            </div>
            @endif
          </div>
        </div>
        @endif

        @if($purchase->status_history && $purchase->status_history->count() > 0)
        <div class="card mt-4">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Status History') }}</h5>
          </div>
          <div class="card-body">
            <div class="timeline">
              @foreach($purchase->status_history ? $purchase->status_history->sortByDesc('created_at') : [] as $history)
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
              <span>${{ number_format($purchase->subtotal, 2) }}</span>
            </div>

            @if($purchase->discount_amount > 0)
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Discount') }}:</span>
              <span class="text-danger">-${{ number_format($purchase->discount_amount, 2) }}</span>
            </div>
            @endif

            @if($purchase->tax_amount > 0)
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Tax') }}:</span>
              <span>${{ number_format($purchase->tax_amount, 2) }}</span>
            </div>
            @endif

            @if($purchase->shipping_cost > 0)
            <div class="d-flex justify-content-between mb-2">
              <span>{{ __('Shipping Cost') }}:</span>
              <span>${{ number_format($purchase->shipping_cost, 2) }}</span>
            </div>
            @endif

            <hr>

            <div class="d-flex justify-content-between">
              <strong>{{ __('Total Amount') }}:</strong>
              <strong>${{ number_format($purchase->total_amount, 2) }}</strong>
            </div>

            @if($purchase->payment_status)
            <hr>
            <div class="d-flex justify-content-between">
              <span>{{ __('Payment Status') }}:</span>
              <span>
                @switch($purchase->payment_status)
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

        @if($purchase->created_by || $purchase->updated_by)
        <div class="card mt-4">
          <div class="card-header">
            <h5 class="card-title mb-0">{{ __('Audit Trail') }}</h5>
          </div>
          <div class="card-body">
            @if($purchase->created_by)
            <div class="mb-2">
              <strong>{{ __('Created by') }}:</strong><br>
              <small class="text-muted">
                {{ $purchase->createdBy->name ?? 'Unknown' }}<br>
                {{ $purchase->created_at->format('M j, Y g:i A') }}
              </small>
            </div>
            @endif

            @if($purchase->updated_by && $purchase->updated_at != $purchase->created_at)
            <div class="mb-2">
              <strong>{{ __('Last updated by') }}:</strong><br>
              <small class="text-muted">
                {{ $purchase->updatedBy->name ?? 'Unknown' }}<br>
                {{ $purchase->updated_at->format('M j, Y g:i A') }}
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