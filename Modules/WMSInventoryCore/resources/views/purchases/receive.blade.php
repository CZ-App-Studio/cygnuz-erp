@extends('layouts.layoutMaster')

@section('title', __('Receive Purchase Order') . ' - ' . $purchase->code)

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        processReceiving: @json(route('wmsinventorycore.purchases.receive', $purchase->id)),
        showPurchase: @json(route('wmsinventorycore.purchases.show', $purchase->id))
      },
      labels: {
        success: @json(__('Success!')),
        itemsReceived: @json(__('Items have been received successfully.')),
        error: @json(__('Error!')),
        validationError: @json(__('Please check the form for errors.')),
        confirmReceive: @json(__('Confirm Receiving')),
        confirmReceiveText: @json(__('Are you sure you want to process these received items?')),
        selectAtLeastOne: @json(__('Please select at least one item to receive.'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-purchase-receive.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Purchase Orders'), 'url' => route('wmsinventorycore.purchases.index')],
    ['name' => $purchase->code, 'url' => route('wmsinventorycore.purchases.show', $purchase->id)]
  ];
@endphp

<x-breadcrumb
  :title="__('Receive Purchase Order') . ' - ' . $purchase->code"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-12">
    <!-- Purchase Order Summary -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Purchase Order Summary') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('PO Number') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->code }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Vendor') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->vendor->name }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Warehouse') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->warehouse->name }}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('PO Date') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->date ? $purchase->date->format('F j, Y') : '-' }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Expected Delivery') }}:</strong></div>
              <div class="col-sm-8">{{ $purchase->expected_delivery_date ? $purchase->expected_delivery_date->format('F j, Y') : '-' }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Total Amount') }}:</strong></div>
              <div class="col-sm-8">${{ number_format($purchase->total_amount, 2) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Receiving Form -->
    <form id="receiveForm">
      @csrf

      <!-- Receiving Information -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Receiving Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="received_date">{{ __('Received Date') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="received_date" name="received_date" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label" for="received_by">{{ __('Received By') }}</label>
              <input type="text" class="form-control" id="received_by" name="received_by" value="{{ auth()->user()->name }}" placeholder="{{ __('Enter receiver name') }}" />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label" for="delivery_note">{{ __('Delivery Note/Reference') }}</label>
              <input type="text" class="form-control" id="delivery_note" name="delivery_note" placeholder="{{ __('Enter delivery note reference') }}" />
            </div>
          </div>
          <div class="row">
            <div class="col-12 mb-3">
              <label class="form-label" for="receiving_notes">{{ __('Receiving Notes') }}</label>
              <textarea class="form-control" id="receiving_notes" name="receiving_notes" rows="3" placeholder="{{ __('Enter any notes about the receiving process') }}"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Items to Receive -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Items to Receive') }}</h5>
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="select-all">
            <label class="form-check-label" for="select-all">{{ __('Select All') }}</label>
          </div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th style="width: 50px;">{{ __('Receive') }}</th>
                  <th>{{ __('Product') }}</th>
                  <th>{{ __('SKU') }}</th>
                  <th class="text-end">{{ __('Ordered Qty') }}</th>
                  <th class="text-end">{{ __('Previously Received') }}</th>
                  <th class="text-end">{{ __('Remaining') }}</th>
                  <th class="text-end">{{ __('Qty to Receive') }}</th>
                  <th>{{ __('Status') }}</th>
                  <th>{{ __('Notes') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($purchase->products->filter(function($item) { return $item->quantity > ($item->received_quantity ?? 0); }) as $index => $item)
                @php
                  $remainingQty = $item->quantity - ($item->received_quantity ?? 0);
                @endphp
                <tr>
                  <td class="text-center">
                    <div class="form-check">
                      <input type="checkbox" class="form-check-input receive-item" name="items[{{ $index }}][receive]" value="1">
                    </div>
                  </td>
                  <td>
                    <strong>{{ $item->product->name }}</strong>
                    <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                  </td>
                  <td>{{ $item->product->code ?? $item->product->sku ?? '-' }}</td>
                  <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                  <td class="text-end">{{ number_format($item->received_quantity ?? 0, 2) }}</td>
                  <td class="text-end">{{ number_format($remainingQty, 2) }}</td>
                  <td>
                    <input type="number" 
                           class="form-control form-control-sm quantity-received text-end" 
                           name="items[{{ $index }}][quantity_received]" 
                           min="0" 
                           max="{{ $remainingQty }}" 
                           step="0.01" 
                           disabled>
                  </td>
                  <td>
                    <div class="d-flex flex-column gap-1">
                      <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="items[{{ $index }}][status]" value="accepted" disabled>
                        <label class="form-check-label">{{ __('Accepted') }}</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="items[{{ $index }}][status]" value="rejected" disabled>
                        <label class="form-check-label">{{ __('Rejected') }}</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="items[{{ $index }}][status]" value="damaged" disabled>
                        <label class="form-check-label">{{ __('Damaged') }}</label>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="form-group" style="display: none;">
                      <textarea class="form-control form-control-sm rejection-notes" 
                                name="items[{{ $index }}][notes]" 
                                rows="2" 
                                placeholder="{{ __('Reason for rejection/damage') }}" 
                                disabled></textarea>
                    </div>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="9" class="text-center py-4">
                    <div class="text-muted">
                      <i class="bx bx-package bx-lg mb-2"></i>
                      <p>{{ __('All items have been fully received or there are no items to receive.') }}</p>
                    </div>
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="card">
        <div class="card-body">
          <div class="d-flex gap-3">
            <button type="submit" class="btn btn-primary" id="receive-btn" disabled>
              <i class="bx bx-package"></i> {{ __('Process Receiving') }}
            </button>
            <a href="{{ route('wmsinventorycore.purchases.show', $purchase->id) }}" class="btn btn-label-secondary">
              <i class="bx bx-arrow-back"></i> {{ __('Back to Purchase Order') }}
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

@if($purchase->products->where('received_quantity', '>', 0)->count() > 0)
<!-- Previously Received Items -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Previously Received Items') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('SKU') }}</th>
                <th class="text-end">{{ __('Ordered Qty') }}</th>
                <th class="text-end">{{ __('Received Qty') }}</th>
                <th class="text-end">{{ __('Remaining') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($purchase->products->where('received_quantity', '>', 0) as $item)
              <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->code ?? $item->product->sku ?? '-' }}</td>
                <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-end">{{ number_format($item->received_quantity, 2) }}</td>
                <td class="text-end">{{ number_format($item->quantity - $item->received_quantity, 2) }}</td>
                <td>
                  @if($item->received_quantity >= $item->quantity)
                    <span class="badge bg-success">{{ __('Complete') }}</span>
                  @else
                    <span class="badge bg-warning">{{ __('Partial') }}</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
@endsection