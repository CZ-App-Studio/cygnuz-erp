@extends('layouts.layoutMaster')

@section('title', __('Create Transfer'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        transfersIndex: @json(route('wmsinventorycore.transfers.index')),
        warehouseProducts: @json(route('wmsinventorycore.transfers.warehouse-products'))
      },
      data: {
        warehouses: @json($warehouses),
        nextCode: @json($nextCode)
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-transfer-form.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Transfers'), 'url' => route('wmsinventorycore.transfers.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Create New Transfer')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('wmsinventorycore.dashboard.index')"
/>

<div class="row">
  <div class="col-md-12">
    <form action="{{ route('wmsinventorycore.transfers.store') }}" method="POST" id="transferForm">
      @csrf
      
      <!-- Hidden code field -->
      <input type="hidden" name="code" value="{{ $nextCode }}" />
      
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Transfer Information') }}</h5>
          <a href="{{ route('wmsinventorycore.transfers.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> {{ __('Back to List') }}
          </a>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="date">{{ __('Transfer Date') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control flatpickr-date @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required />
              @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="source_warehouse_id">{{ __('Source Warehouse') }} <span class="text-danger">*</span></label>
              <select class="form-select select2 @error('source_warehouse_id') is-invalid @enderror" id="source_warehouse_id" name="source_warehouse_id" required>
                <option value="">{{ __('Select Source Warehouse') }}</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('source_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
              </select>
              @error('source_warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="destination_warehouse_id">{{ __('Destination Warehouse') }} <span class="text-danger">*</span></label>
              <select class="form-select select2 @error('destination_warehouse_id') is-invalid @enderror" id="destination_warehouse_id" name="destination_warehouse_id" required>
                <option value="">{{ __('Select Destination Warehouse') }}</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('destination_warehouse_id') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
              </select>
              @error('destination_warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="reference_no">{{ __('Reference No.') }}</label>
              <input type="text" class="form-control @error('reference_no') is-invalid @enderror" id="reference_no" name="reference_no" value="{{ old('reference_no') }}" />
              @error('reference_no')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="expected_arrival_date">{{ __('Expected Arrival Date') }}</label>
              <input type="text" class="form-control flatpickr-date @error('expected_arrival_date') is-invalid @enderror" id="expected_arrival_date" name="expected_arrival_date" value="{{ old('expected_arrival_date') }}" />
              @error('expected_arrival_date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="shipping_cost">{{ __('Shipping Cost') }}</label>
              <input type="number" class="form-control @error('shipping_cost') is-invalid @enderror" id="shipping_cost" name="shipping_cost" value="{{ old('shipping_cost', '0.00') }}" step="0.01" min="0" />
              @error('shipping_cost')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
      
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">{{ __('Products') }}</h5>
        </div>
        <div class="card-body">
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
                <!-- Products will be added here -->
                @if(old('products'))
                  @foreach(old('products') as $index => $product)
                    <tr class="product-row" data-product-id="{{ $product['product_id'] }}">
                      <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $product['product_id'] }}">
                      <td>{{ $product['product_name'] ?? __('Unknown Product') }}</td>
                      <td class="available-stock">{{ $product['available_stock'] ?? 0 }}</td>
                      <td>
                        <input type="number" class="form-control" name="products[{{ $index }}][quantity]" value="{{ $product['quantity'] }}" min="0.01" step="0.01" required>
                      </td>
                      <td>
                        <input type="text" class="form-control" name="products[{{ $index }}][notes]" value="{{ $product['notes'] }}">
                      </td>
                      <td>
                        <button type="button" class="btn btn-sm btn-danger remove-product">
                          <i class="bx bx-trash"></i>
                        </button>
                      </td>
                    </tr>
                  @endforeach
                @endif
              </tbody>
            </table>
          </div>
          
          <div id="no-products-message" class="text-center py-3 {{ old('products') ? 'd-none' : '' }}">
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
                <button type="submit" class="btn btn-primary" id="submit-btn">
                  {{ __('Create Transfer') }}
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
@endsection
