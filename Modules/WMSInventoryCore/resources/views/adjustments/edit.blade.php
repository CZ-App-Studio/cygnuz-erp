@extends('layouts.layoutMaster')

@section('title', __('Edit Adjustment'))

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
        adjustmentsIndex: @json(route('wmsinventorycore.adjustments.index')),
        adjustmentsUpdate: @json(route('wmsinventorycore.adjustments.update', $adjustment->id)),
        warehouseProducts: @json(route('wmsinventorycore.adjustments.warehouse-products'))
      },
      data: {
        warehouses: @json($warehouses),
        adjustmentTypes: @json($adjustmentTypes),
        adjustment: @json($adjustment->load('products'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-adjustment-form.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Adjustments'), 'url' => route('wmsinventorycore.adjustments.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Edit Adjustment')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('wmsinventorycore.dashboard.index')"
/>

<div class="row">
  <div class="col-md-12">
    <form action="{{ route('wmsinventorycore.adjustments.update', $adjustment->id) }}" method="POST" id="adjustmentForm">
      @csrf
      @method('PUT')
      
      <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">{{ __('Adjustment Information') }}</h5>
          <a href="{{ route('wmsinventorycore.adjustments.index') }}" class="btn btn-secondary">
            <i class="bx bx-arrow-back me-1"></i> {{ __('Back to List') }}
          </a>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="date">{{ __('Adjustment Date') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control flatpickr-date @error('date') is-invalid @enderror" id="date" name="date" value="{{ old('date', $adjustment->date->format('Y-m-d')) }}" required />
              @error('date')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="warehouse_id">{{ __('Warehouse') }} <span class="text-danger">*</span></label>
              <select class="form-select select2 @error('warehouse_id') is-invalid @enderror" id="warehouse_id" name="warehouse_id" required {{ $adjustment->status !== 'pending' ? 'disabled' : '' }}>
                <option value="">{{ __('Select Warehouse') }}</option>
                @foreach($warehouses as $warehouse)
                  <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $adjustment->warehouse_id) == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                @endforeach
              </select>
              @if($adjustment->status !== 'pending')
                <input type="hidden" name="warehouse_id" value="{{ $adjustment->warehouse_id }}">
              @endif
              @error('warehouse_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="adjustment_type_id">{{ __('Adjustment Type') }} <span class="text-danger">*</span></label>
              <select class="form-select select2 @error('adjustment_type_id') is-invalid @enderror" id="adjustment_type_id" name="adjustment_type_id" required {{ $adjustment->status !== 'pending' ? 'disabled' : '' }}>
                <option value="">{{ __('Select Type') }}</option>
                @foreach($adjustmentTypes as $type)
                  <option value="{{ $type->id }}" data-effect="{{ $type->effect }}" {{ old('adjustment_type_id', $adjustment->adjustment_type_id) == $type->id ? 'selected' : '' }}>{{ $type->name }} ({{ ucfirst($type->effect) }})</option>
                @endforeach
              </select>
              @if($adjustment->status !== 'pending')
                <input type="hidden" name="adjustment_type_id" value="{{ $adjustment->adjustment_type_id }}">
              @endif
              @error('adjustment_type_id')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="reference_no">{{ __('Reference No.') }}</label>
            <input type="text" class="form-control @error('reference_no') is-invalid @enderror" id="reference_no" name="reference_no" value="{{ old('reference_no', $adjustment->reference_no) }}" />
            @error('reference_no')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="reason">{{ __('Reason') }} <span class="text-danger">*</span></label>
            <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="3" required>{{ old('reason', $adjustment->reason) }}</textarea>
            @error('reason')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="notes">{{ __('Notes') }}</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $adjustment->notes) }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          @if($adjustment->status !== 'pending')
            <div class="alert alert-info">
              <i class="bx bx-info-circle me-1"></i>
              {{ __('This adjustment has already been processed. Some fields cannot be changed.') }}
            </div>
          @endif
        </div>
      </div>
      
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0">Products</h5>
        </div>
        <div class="card-body">
          @if($adjustment->status === 'pending')
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
                  <th width="15%">{{ __('Current Stock') }}</th>
                  <th width="20%">{{ __('Quantity') }}</th>
                  <th width="20%">{{ __('Reason') }}</th>
                  <th width="15%">{{ __('Actions') }}</th>
                </tr>
              </thead>
              <tbody id="products-container">
                <!-- Products -->
                @foreach($adjustment->products as $index => $item)
                  <tr class="product-row" data-product-id="{{ $item->product_id }}">
                    <input type="hidden" name="products[{{ $index }}][product_id]" value="{{ $item->product_id }}">
                    <input type="hidden" name="products[{{ $index }}][id]" value="{{ $item->id }}">
                    <td>{{ $item->product->name }} ({{ $item->product->sku }})</td>
                    <td class="current-stock">{{ $item->product->getCurrentStock($adjustment->warehouse_id) ?? 0 }}</td>
                    <td>
                      <input type="number" class="form-control" name="products[{{ $index }}][quantity]" value="{{ old('products.' . $index . '.quantity', $item->quantity) }}" min="0.01" step="0.01" required {{ $adjustment->status !== 'pending' ? 'readonly' : '' }}>
                    </td>
                    <td>
                      <input type="text" class="form-control" name="products[{{ $index }}][reason]" value="{{ old('products.' . $index . '.reason', $item->reason) }}" {{ $adjustment->status !== 'pending' ? 'readonly' : '' }}>
                    </td>
                    <td>
                      @if($adjustment->status === 'pending')
                        <button type="button" class="btn btn-sm btn-danger remove-product">
                          <i class="bx bx-trash"></i>
                        </button>
                      @else
                        <span class="badge bg-secondary">{{ __('Processed') }}</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          
          <div id="no-products-message" class="text-center py-3 {{ count($adjustment->products) > 0 ? 'd-none' : '' }}">
            <p class="text-muted mb-0">{{ __('No products added. Use the search box above to add products.') }}</p>
          </div>
        </div>
      </div>
      
      <div class="row">
        <div class="col-12">
          <div class="card mb-4">
            <div class="card-body">
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary me-2" onclick="window.location.href='{{ route('wmsinventorycore.adjustments.index') }}'">
                  {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn btn-primary" id="submit-btn">
                  {{ __('Update Adjustment') }}
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
    <td class="current-stock">{CURRENT_STOCK}</td>
    <td>
      <input type="number" class="form-control" name="products[{INDEX}][quantity]" value="1" min="0.01" step="0.01" required>
    </td>
    <td>
      <input type="text" class="form-control" name="products[{INDEX}][reason]" value="">
    </td>
    <td>
      <button type="button" class="btn btn-sm btn-danger remove-product">
        <i class="bx bx-trash"></i>
      </button>
    </td>
  </tr>
</template>
@endsection
