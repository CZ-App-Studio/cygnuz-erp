@extends('layouts.layoutMaster')

@section('title', __('Edit Product'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/wms-inventory-product-form.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Products'), 'url' => route('wmsinventorycore.products.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Edit Product')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <h5 class="card-header">{{ __('Product Details') }}</h5>
      <div class="card-body">
        <form id="productForm" action="{{ route('wmsinventorycore.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="ai-context" 
                   data-ai-context="Product name: {{ $product->name }}" 
                   data-ai-field-type="title"
                   data-ai-context-category="{{ $product->category->name ?? 'General' }}"
                   data-ai-context-sku="{{ $product->sku }}">
                <label class="form-label" for="name">{{ __('Product Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control ai-field" id="name" name="name" placeholder="{{ __('Enter product name') }}" value="{{ $product->name }}" required />
                @error('name')
                  <div class="text-danger">{{ $message }}</div>
                @enderror
              </div>
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label" for="code">{{ __('Product Code') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="code" name="code" placeholder="{{ __('Enter product code') }}" value="{{ $product->code }}" required />
              @error('code')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="sku">{{ __('SKU') }}</label>
              <input type="text" class="form-control" id="sku" name="sku" placeholder="{{ __('Enter SKU') }}" value="{{ $product->sku }}" />
              @error('sku')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label" for="barcode">{{ __('Barcode') }}</label>
              <input type="text" class="form-control" id="barcode" name="barcode" placeholder="{{ __('Enter barcode') }}" value="{{ $product->barcode }}" />
              @error('barcode')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="category_id">{{ __('Category') }} <span class="text-danger">*</span></label>
              <select id="category_id" name="category_id" class="select2 form-select" required data-placeholder="{{ __('Select a category') }}">
                <option value="">{{ __('Select a category') }}</option>
                @foreach($categories as $category)
                  <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
              </select>
              @error('category_id')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label" for="unit_id">{{ __('Unit') }} <span class="text-danger">*</span></label>
              <select id="unit_id" name="unit_id" class="select2 form-select" required data-placeholder="{{ __('Select a unit') }}">
                <option value="">{{ __('Select a unit') }}</option>
                @foreach($units as $unit)
                  <option value="{{ $unit->id }}" {{ $product->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                @endforeach
              </select>
              @error('unit_id')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="mb-3">
            <div class="ai-context" 
                 data-ai-context="Product: {{ $product->name }}" 
                 data-ai-field-type="product_description"
                 data-ai-context-category="{{ $product->category->name ?? 'General' }}"
                 data-ai-context-sku="{{ $product->sku }}"
                 data-ai-context-code="{{ $product->code }}"
                 data-ai-context-price="{{ $product->selling_price }}"
                 data-ai-context-usage="{{ __('Warehouse management and inventory tracking') }}">
              <label class="form-label" for="description">{{ __('Description') }}</label>
              <textarea class="form-control ai-field" id="description" name="description" rows="3" placeholder="{{ __('Enter product description - click the AI sparkle icon for help') }}">{{ $product->description }}</textarea>
              @error('description')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <!-- Pricing Section -->
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="cost_price">{{ __('Cost Price') }}</label>
              <input type="number" step="0.01" class="form-control" id="cost_price" name="cost_price" placeholder="{{ __('Enter cost price') }}" value="{{ $product->cost_price }}" />
              @error('cost_price')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label" for="selling_price">{{ __('Selling Price') }}</label>
              <input type="number" step="0.01" class="form-control" id="selling_price" name="selling_price" placeholder="{{ __('Enter selling price') }}" value="{{ $product->selling_price }}" />
              @error('selling_price')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="alert_on">{{ __('Alert Quantity') }}</label>
              <input type="number" class="form-control" id="alert_on" name="alert_on" placeholder="{{ __('Enter alert quantity') }}" value="{{ $product->alert_on }}" />
              @error('alert_on')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-6 mb-3">
              <label class="form-label" for="status">{{ __('Status') }}</label>
              <select id="status" name="status" class="form-select">
                <option value="active" {{ $product->status == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="inactive" {{ $product->status == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
              </select>
              @error('status')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="track_weight" name="track_weight" {{ $product->track_weight ? 'checked' : '' }} />
                <label class="form-check-label" for="track_weight">{{ __('Track Weight') }}</label>
              </div>
              @error('track_weight')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-6 mb-3">
              <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" id="track_quantity" name="track_quantity" {{ $product->track_quantity ? 'checked' : '' }} />
                <label class="form-check-label" for="track_quantity">{{ __('Track Quantity') }}</label>
              </div>
              @error('track_quantity')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          @if(\Modules\WMSInventoryCore\app\Services\WMSInventoryCoreSettingsService::enableBatchTracking())
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="batch_number">{{ __('Batch Number') }}</label>
              <input type="text" class="form-control" id="batch_number" name="batch_number" value="{{ $product->batch_number ?? '' }}" placeholder="{{ __('Enter batch number') }}" />
              @error('batch_number')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            
            @if(\Modules\WMSInventoryCore\app\Services\WMSInventoryCoreSettingsService::enableExpiryTracking())
            <div class="col-md-6 mb-3">
              <label class="form-label" for="expiry_date">{{ __('Expiry Date') }}</label>
              <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="{{ $product->expiry_date ?? '' }}" />
              @error('expiry_date')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
            @endif
          </div>
          @endif
          
          @if(\Modules\WMSInventoryCore\app\Services\WMSInventoryCoreSettingsService::enableSerialTracking())
          <div class="row">
            <div class="col-md-12 mb-3">
              <label class="form-label" for="serial_numbers">{{ __('Serial Numbers') }}</label>
              <textarea class="form-control" id="serial_numbers" name="serial_numbers" rows="3" placeholder="{{ __('Enter serial numbers (one per line)') }}">{{ $product->serial_numbers ?? '' }}</textarea>
              <small class="text-muted">{{ __('Enter each serial number on a separate line') }}</small>
              @error('serial_numbers')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>
          </div>
          @endif
          
          <div class="mb-3">
            <label for="image" class="form-label">
              {{ __('Product Image') }}
              @if(module_setting('WMSInventoryCore', 'require_product_images', false) && !$product->hasProductImage())
                <span class="text-danger">*</span>
              @endif
            </label>
            <input type="file" class="form-control" id="image" name="image" accept="image/*" @if(module_setting('WMSInventoryCore', 'require_product_images', false) && !$product->hasProductImage()) required @endif>
            <small class="form-text text-muted">{{ __('Supported formats: JPG, PNG, GIF. Max size: 2MB') }}</small>
            @if($product->hasProductImage())
              <div class="mt-2">
                <p>{{ __('Current Image') }}:</p>
                <img src="{{ $product->getProductImageUrl() }}" alt="{{ $product->name }}" class="img-fluid" style="max-width: 200px;">
                <p><small class="text-muted">{{ __('Upload a new image to replace the current one') }}</small></p>
              </div>
            @endif
            @error('image')
              <div class="text-danger">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="pt-4">
            <button type="submit" class="btn btn-primary me-sm-3 me-1">{{ __('Update') }}</button>
            <a href="{{ route('wmsinventorycore.products.index') }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
