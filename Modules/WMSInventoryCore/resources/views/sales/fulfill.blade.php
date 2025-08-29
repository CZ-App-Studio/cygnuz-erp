@extends('layouts.layoutMaster')

@section('title', __('Fulfill Sales Order') . ' - ' . $sale->code)

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
        processFulfillment: @json(route('wmsinventorycore.sales.fulfill', $sale->id)),
        showSale: @json(route('wmsinventorycore.sales.show', $sale->id))
      },
      labels: {
        success: @json(__('Success!')),
        itemsFulfilled: @json(__('Items have been fulfilled successfully.')),
        error: @json(__('Error!')),
        validationError: @json(__('Please check the form for errors.')),
        confirmFulfill: @json(__('Confirm Fulfillment')),
        confirmFulfillText: @json(__('Are you sure you want to process these fulfilled items?')),
        selectAtLeastOne: @json(__('Please select at least one item to fulfill.'))
      }
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-sale-fulfill.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Sales Orders'), 'url' => route('wmsinventorycore.sales.index')],
    ['name' => $sale->code, 'url' => route('wmsinventorycore.sales.show', $sale->id)]
  ];
@endphp

<x-breadcrumb
  :title="__('Fulfill Sales Order') . ' - ' . $sale->code"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-12">
    <!-- Sales Order Summary -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Sales Order Summary') }}</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('SO Number') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->code }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Customer') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->customer->name }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Warehouse') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->warehouse->name }}</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Sale Date') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->date ? $sale->date->format('F j, Y') : '-' }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Expected Delivery') }}:</strong></div>
              <div class="col-sm-8">{{ $sale->expected_delivery_date ? $sale->expected_delivery_date->format('F j, Y') : '-' }}</div>
            </div>
            <div class="row mb-2">
              <div class="col-sm-4"><strong>{{ __('Total Amount') }}:</strong></div>
              <div class="col-sm-8">${{ number_format($sale->total_amount, 2) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Fulfillment Form -->
    <form id="fulfillForm">
      @csrf

      <!-- Fulfillment Information -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Fulfillment Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label" for="fulfilled_date">{{ __('Fulfillment Date') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="fulfilled_date" name="fulfilled_date" required />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label" for="fulfilled_by">{{ __('Fulfilled By') }}</label>
              <input type="text" class="form-control" id="fulfilled_by" name="fulfilled_by" value="{{ auth()->user()->name }}" placeholder="{{ __('Enter fulfillment person name') }}" />
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label" for="packing_slip">{{ __('Packing Slip/Reference') }}</label>
              <input type="text" class="form-control" id="packing_slip" name="packing_slip" placeholder="{{ __('Enter packing slip reference') }}" />
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="shipping_method">{{ __('Shipping Method') }}</label>
              <select class="form-select" id="shipping_method" name="shipping_method">
                <option value="">{{ __('Select Shipping Method') }}</option>
                <option value="standard">{{ __('Standard Shipping') }}</option>
                <option value="express">{{ __('Express Shipping') }}</option>
                <option value="overnight">{{ __('Overnight Shipping') }}</option>
                <option value="pickup">{{ __('Customer Pickup') }}</option>
                <option value="local_delivery">{{ __('Local Delivery') }}</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label" for="tracking_number">{{ __('Tracking Number') }}</label>
              <input type="text" class="form-control" id="tracking_number" name="tracking_number" placeholder="{{ __('Enter tracking number') }}" />
            </div>
          </div>
          <div class="row">
            <div class="col-12 mb-3">
              <label class="form-label" for="fulfillment_notes">{{ __('Fulfillment Notes') }}</label>
              <textarea class="form-control" id="fulfillment_notes" name="fulfillment_notes" rows="3" placeholder="{{ __('Enter any notes about the fulfillment process') }}"></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Items to Fulfill -->
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0">{{ __('Items to Fulfill') }}</h5>
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
                  <th style="width: 50px;">{{ __('Fulfill') }}</th>
                  <th>{{ __('Product') }}</th>
                  <th>{{ __('SKU') }}</th>
                  <th class="text-end">{{ __('Ordered Qty') }}</th>
                  <th class="text-end">{{ __('Previously Fulfilled') }}</th>
                  <th class="text-end">{{ __('Remaining') }}</th>
                  <th class="text-end">{{ __('Qty to Fulfill') }}</th>
                  <th>{{ __('Status') }}</th>
                  <th>{{ __('Notes') }}</th>
                </tr>
              </thead>
              <tbody>
                @forelse($sale->products->filter(function($item) { return $item->quantity > ($item->fulfilled_quantity ?? 0); }) as $index => $item)
                @php
                  $remainingQty = $item->quantity - ($item->fulfilled_quantity ?? 0);
                @endphp
                <tr>
                  <td class="text-center">
                    <div class="form-check">
                      <input type="checkbox" class="form-check-input fulfill-item" name="items[{{ $index }}][fulfill]" value="1">
                    </div>
                  </td>
                  <td>
                    <strong>{{ $item->product->name }}</strong>
                    <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                    <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                  </td>
                  <td>{{ $item->product->code ?? $item->product->sku ?? '-' }}</td>
                  <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                  <td class="text-end">{{ number_format($item->fulfilled_quantity ?? 0, 2) }}</td>
                  <td class="text-end">{{ number_format($remainingQty, 2) }}</td>
                  <td>
                    <input type="number" 
                           class="form-control form-control-sm quantity-fulfilled text-end" 
                           name="items[{{ $index }}][quantity_fulfilled]" 
                           min="0" 
                           max="{{ $remainingQty }}" 
                           step="0.01" 
                           disabled>
                  </td>
                  <td>
                    <div class="d-flex flex-column gap-1">
                      <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="items[{{ $index }}][status]" value="fulfilled" disabled>
                        <label class="form-check-label">{{ __('Fulfilled') }}</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="items[{{ $index }}][status]" value="back_ordered" disabled>
                        <label class="form-check-label">{{ __('Back Ordered') }}</label>
                      </div>
                      <div class="form-check form-check-inline">
                        <input type="radio" class="form-check-input" name="items[{{ $index }}][status]" value="cancelled" disabled>
                        <label class="form-check-label">{{ __('Cancelled') }}</label>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div class="form-group" style="display: none;">
                      <textarea class="form-control form-control-sm fulfillment-notes" 
                                name="items[{{ $index }}][notes]" 
                                rows="2" 
                                placeholder="{{ __('Reason for back order/cancellation') }}" 
                                disabled></textarea>
                    </div>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="9" class="text-center py-4">
                    <div class="text-muted">
                      <i class="bx bx-package bx-lg mb-2"></i>
                      <p>{{ __('All items have been fully fulfilled or there are no items to fulfill.') }}</p>
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
            <button type="submit" class="btn btn-primary" id="fulfill-btn" disabled>
              <i class="bx bx-package"></i> {{ __('Process Fulfillment') }}
            </button>
            <a href="{{ route('wmsinventorycore.sales.show', $sale->id) }}" class="btn btn-label-secondary">
              <i class="bx bx-arrow-back"></i> {{ __('Back to Sales Order') }}
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

@if($sale->products->where('fulfilled_quantity', '>', 0)->count() > 0)
<!-- Previously Fulfilled Items -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Previously Fulfilled Items') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm">
            <thead>
              <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('SKU') }}</th>
                <th class="text-end">{{ __('Ordered Qty') }}</th>
                <th class="text-end">{{ __('Fulfilled Qty') }}</th>
                <th class="text-end">{{ __('Remaining') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
              @foreach($sale->products->where('fulfilled_quantity', '>', 0) as $item)
              <tr>
                <td>{{ $item->product->name }}</td>
                <td>{{ $item->product->code ?? $item->product->sku ?? '-' }}</td>
                <td class="text-end">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-end">{{ number_format($item->fulfilled_quantity, 2) }}</td>
                <td class="text-end">{{ number_format($item->quantity - $item->fulfilled_quantity, 2) }}</td>
                <td>
                  @if($item->fulfilled_quantity >= $item->quantity)
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