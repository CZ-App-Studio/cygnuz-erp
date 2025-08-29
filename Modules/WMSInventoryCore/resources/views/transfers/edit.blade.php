@extends('layouts.layoutMaster')

@section('title', __('Edit Transfer'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/select2/select2.scss'])
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/select2/select2.js'])
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@vite(['resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        transfersIndex: @json(route('wmsinventorycore.transfers.index')),
        warehouseProducts: @json(route('wmsinventorycore.transfers.warehouse-products')),
        transfersShip: @json(route('wmsinventorycore.transfers.ship', ['transfer' => $transfer->id])),
        transfersReceive: @json(route('wmsinventorycore.transfers.receive', ['transfer' => $transfer->id])),
        transfersCancel: @json(route('wmsinventorycore.transfers.cancel', ['transfer' => $transfer->id])),
        transfersDelete: @json(route('wmsinventorycore.transfers.destroy', ['transfer' => $transfer->id]))
      },
      data: {
        warehouses: @json($warehouses),
        transfer: @json($transfer->load('products.product')),
        availableStock: @json($availableStock ?? [])
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-transfer-form.js'])
  @vite(['resources/assets/js/app/wms-inventory-transfers.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Transfers'), 'url' => route('wmsinventorycore.transfers.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Edit Transfer')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('wmsinventorycore.dashboard.index')"
/>

<div class="row">
  <div class="col-md-12">
    <form action="{{ route('wmsinventorycore.transfers.update', $transfer->id) }}" method="POST" id="transferForm">
      @csrf
      @method('PUT')
      
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Transfer Information') }}</h5>
          <div>
            <a href="{{ route('wmsinventorycore.transfers.index') }}" class="btn btn-secondary me-2">
              <i class="bx bx-arrow-back me-1"></i> {{ __('Back to List') }}
            </a>
            @if($transfer->status == 'draft')
              <form method="POST" action="{{ route('wmsinventorycore.transfers.approve', $transfer->id) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-success btn-sm me-1" onclick="return confirm('{{ __('Are you sure you want to approve this transfer?') }}')">
                  <i class="bx bx-check me-1"></i> {{ __('Approve') }}
                </button>
              </form>
            @elseif($transfer->status == 'approved')
              <button type="button" class="btn btn-primary btn-sm me-1 ship-record" data-id="{{ $transfer->id }}">
                <i class="bx bx-send me-1"></i> {{ __('Ship') }}
              </button>
              <button type="button" class="btn btn-danger btn-sm cancel-record" data-id="{{ $transfer->id }}">
                <i class="bx bx-x-circle me-1"></i> {{ __('Cancel') }}
              </button>
            @elseif($transfer->status == 'in_transit')
              <button type="button" class="btn btn-success btn-sm me-1 receive-record" data-id="{{ $transfer->id }}">
                <i class="bx bx-check-circle me-1"></i> {{ __('Receive') }}
              </button>
              <button type="button" class="btn btn-danger btn-sm cancel-record" data-id="{{ $transfer->id }}">
                <i class="bx bx-x-circle me-1"></i> {{ __('Cancel') }}
              </button>
            @endif
          </div>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="date">{{ __('Transfer Date') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control flatpickr-date @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : '') }}" required {{ $transfer->status !== 'draft' ? 'readonly' : '' }} />
              @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="source_warehouse_id">{{ __('Source Warehouse') }} <span class="text-danger">*</span></label>
              <select class="form-select select2 @error('source_warehouse_id') is-invalid @enderror" id="source_warehouse_id" name="source_warehouse_id" required {{ $transfer->status !== 'draft' ? 'disabled' : '' }}>
                <option value="">{{ __('Select Source Warehouse') }}</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('source_warehouse_id', $transfer->source_warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
              </select>
              @if($transfer->status !== 'draft')
                <input type="hidden" name="source_warehouse_id" value="{{ $transfer->source_warehouse_id }}">
              @endif
              @error('source_warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="destination_warehouse_id">{{ __('Destination Warehouse') }} <span class="text-danger">*</span></label>
              <select class="form-select select2 @error('destination_warehouse_id') is-invalid @enderror" id="destination_warehouse_id" name="destination_warehouse_id" required {{ $transfer->status !== 'draft' ? 'disabled' : '' }}>
                <option value="">{{ __('Select Destination Warehouse') }}</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('destination_warehouse_id', $transfer->destination_warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
              </select>
              @if($transfer->status !== 'draft')
                <input type="hidden" name="destination_warehouse_id" value="{{ $transfer->destination_warehouse_id }}">
              @endif
              @error('destination_warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="reference_no">{{ __('Reference No.') }}</label>
              <input type="text" class="form-control @error('reference_no') is-invalid @enderror" id="reference_no" name="reference_no" value="{{ old('reference_no', $transfer->reference_no) }}" />
              @error('reference_no')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="expected_arrival_date">{{ __('Expected Arrival Date') }}</label>
              <input type="text" class="form-control flatpickr-date @error('expected_arrival_date') is-invalid @enderror" id="expected_arrival_date" name="expected_arrival_date" value="{{ old('expected_arrival_date', $transfer->expected_arrival_date ? $transfer->expected_arrival_date->format('Y-m-d') : '') }}" />
              @error('expected_arrival_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="shipping_cost">{{ __('Shipping Cost') }}</label>
              <input type="number" class="form-control @error('shipping_cost') is-invalid @enderror" id="shipping_cost" name="shipping_cost" value="{{ old('shipping_cost', $transfer->shipping_cost) }}" step="0.01" min="0" />
              @error('shipping_cost')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $transfer->notes) }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="mb-3">
            <label class="form-label">{{ __('Status') }}</label>
            <div class="d-flex align-items-center">
              <span class="badge bg-{{ $transfer->status == 'draft' ? 'secondary' : ($transfer->status == 'in_transit' ? 'warning' : ($transfer->status == 'completed' ? 'success' : 'danger')) }} me-2">
                {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
              </span>
              
              @if($transfer->status == 'in_transit')
                <button type="button" class="btn btn-sm btn-success me-2" id="receive-transfer" data-id="{{ $transfer->id }}">
                  <i class="bx bx-check-circle me-1"></i> {{ __('Receive Transfer') }}
                </button>
              @elseif($transfer->status == 'draft')
                <button type="button" class="btn btn-sm btn-primary me-2" id="ship-transfer" data-id="{{ $transfer->id }}">
                  <i class="bx bx-send me-1"></i> {{ __('Ship Transfer') }}
                </button>
              @endif
              
              @if($transfer->status == 'draft')
                <button type="button" class="btn btn-sm btn-danger" id="cancel-transfer" data-id="{{ $transfer->id }}">
                  <i class="bx bx-x-circle me-1"></i> {{ __('Cancel Transfer') }}
                </button>
              @endif
            </div>
          </div>
        </div>
      </div>
      
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Products') }}</h5>
        </div>
        <div class="card-body">
          @if($transfer->status == 'draft')
            <div class="mb-3">
              <div class="row mb-3">
                <div class="col-md-8">
                  <select class="select2 form-select" id="product-search" data-placeholder="{{ __('Search for products...') }}">
                    <option value=""></option>
                  </select>
                </div>
                <div class="col-md-4">
                  <button type="button" class="btn btn-primary" id="add-product-btn">
                    <i class="bx bx-plus me-1"></i> {{ __('Add Product') }}
                  </button>
                </div>
              </div>
            </div>
          @endif
          
          <div class="table-responsive">
            <table class="table table-bordered" id="products-table">
              <thead>
                <tr>
                  <th width="30%">{{ __('Product') }}</th>
                  <th width="15%">{{ __('Available Stock') }}</th>
                  <th width="20%">{{ __('Transfer Quantity') }}</th>
                  <th width="20%">{{ __('Notes') }}</th>
                  <th width="15%">{{ __('Actions') }}</th>
                </tr>
              </thead>
              <tbody id="products-container">
                <!-- Products -->
                @foreach($transfer->products as $index => $item)
                  <tr class="product-row" data-product-id="{{ $item->product_id }}">
                    <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                    <input type="hidden" name="products[{{ $index }}][id]" value="{{ $item->id }}">
                    <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                    <td class="available-stock">{{ $availableStock[$item->product_id] ?? 0 }}</td>
                    <td>
                      <input type="number" class="form-control" name="products[{{ $index }}][quantity]" value="{{ old('products.' . $index . '.quantity', $item->quantity) }}" min="0.01" step="0.01" required {{ $transfer->status !== 'draft' ? 'readonly' : '' }}>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="products[{{ $index }}][notes]" value="{{ old('products.' . $index . '.notes', $item->notes) }}" {{ $transfer->status !== 'draft' ? 'readonly' : '' }}>
                    </td>
                    <td>
                      @if($transfer->status == 'draft')
                        <button type="button" class="btn btn-sm btn-danger remove-product">
                          <i class="bx bx-trash"></i>
                        </button>
                      @else
                        <span class="badge bg-info">{{ ucfirst($transfer->status) }}</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          
          <div id="no-products-message" class="text-center py-3 {{ count($transfer->products) > 0 ? 'd-none' : '' }}">
            <p class="text-muted mb-0">{{ __('No products added. Use the search box above to add products.') }}</p>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-12">
          <div class="card mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary me-2" onclick="window.location.href='{{ route('wmsinventorycore.transfers.index') }}'">
                  {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary" id="submit-btn" {{ $transfer->status !== 'draft' ? 'disabled' : '' }}>
                  {{ __('Update Transfer') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Product Template (Hidden) -->
<template id="product-row-template">
  <tr class="product-row" data-product-id="{PRODUCT_ID}">
    <input type="hidden" name="products[{INDEX}][product_id]" value="{PRODUCT_ID}">
    <td>{PRODUCT_NAME}</td>
    <td class="available-stock">{AVAILABLE_STOCK}</td>
    <td>
      <input type="number" class="form-control" name="products[{INDEX}][quantity]" value="1" min="0.01" step="0.01" required>
    </td>
    <td>
      <input type="text" class="form-control" name="products[{INDEX}][notes]" value="">
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-danger remove-product">
        <i class="bx bx-trash"></i>
      </button>
    </td>
  </tr>
</template>

<!-- Ship Transfer Modal -->
<div class="modal fade" id="shipTransferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Ship Transfer') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>{{ __('Are you sure you want to mark this transfer as shipped? This will deduct stock from the source warehouse.') }}</p>
        <form id="shipTransferForm" action="{{ route('wmsinventorycore.transfers.ship', $transfer->id) }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label" for="actual_ship_date">{{ __('Ship Date') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr-date" id="actual_ship_date" name="actual_ship_date" value="{{ now()->format('Y-m-d') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="shipping_notes">{{ __('Shipping Notes') }}</label>
            <textarea class="form-control" id="shipping_notes" name="shipping_notes" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-primary" id="confirm-ship">{{ __('Ship Transfer') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Receive Transfer Modal -->
<div class="modal fade" id="receiveTransferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Receive Transfer') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>{{ __('Are you sure you want to receive this transfer? This will add stock to the destination warehouse.') }}</p>
        <form id="receiveTransferForm" action="{{ route('wmsinventorycore.transfers.receive', $transfer->id) }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label" for="actual_arrival_date">{{ __('Arrival Date') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control flatpickr-date" id="actual_arrival_date" name="actual_arrival_date" value="{{ now()->format('Y-m-d') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label" for="receiving_notes">{{ __('Receiving Notes') }}</label>
            <textarea class="form-control" id="receiving_notes" name="receiving_notes" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-success" id="confirm-receive">{{ __('Receive Transfer') }}</button>
      </div>
    </div>
  </div>
</div>

<!-- Cancel Transfer Modal -->
<div class="modal fade" id="cancelTransferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Cancel Transfer') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>{{ __('Are you sure you want to cancel this transfer? This action cannot be undone.') }}</p>
        <form id="cancelTransferForm" action="{{ route('wmsinventorycore.transfers.cancel', $transfer->id) }}" method="POST">
          @csrf
          <div class="mb-3">
            <label class="form-label" for="cancellation_reason">{{ __('Cancellation Reason') }} <span class="text-danger">*</span></label>
            <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <button type="button" class="btn btn-danger" id="confirm-cancel">{{ __('Cancel Transfer') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection
