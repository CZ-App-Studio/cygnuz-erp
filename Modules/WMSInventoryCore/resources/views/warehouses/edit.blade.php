@extends('layouts.layoutMaster')

@section('title', __('Edit Warehouse'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        warehousesIndex: @json(route('wmsinventorycore.warehouses.index')),
        warehousesUpdate: @json(route('wmsinventorycore.warehouses.update', $warehouse->id))
      },
      warehouse: @json($warehouse)
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-warehouse-form.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Warehouses'), 'url' => route('wmsinventorycore.warehouses.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Edit Warehouse')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <div class="col-md-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Edit Warehouse Information') }}</h5>
        <a href="{{ route('wmsinventorycore.warehouses.index') }}" class="btn btn-secondary">
          <i class="bx bx-arrow-back me-1"></i> {{ __('Back to List') }}
        </a>
      </div>
      <div class="card-body">
        <form id="warehouseForm" action="#" method="POST">
          @csrf
          @method('PUT')
          
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label" for="name">{{ __('Warehouse Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $warehouse->name) }}" required />
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-6">
              <label class="form-label" for="code">{{ __('Warehouse Code') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $warehouse->code) }}" required />
              @error('code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label" for="address">{{ __('Address') }}</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $warehouse->address) }}</textarea>
            @error('address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label" for="contact_name">{{ __('Contact Person') }}</label>
              <input type="text" class="form-control @error('contact_name') is-invalid @enderror" id="contact_name" name="contact_name" value="{{ old('contact_name', $warehouse->contact_name) }}" />
              @error('contact_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="contact_email">{{ __('Contact Email') }}</label>
              <input type="email" class="form-control @error('contact_email') is-invalid @enderror" id="contact_email" name="contact_email" value="{{ old('contact_email', $warehouse->contact_email) }}" />
              @error('contact_email')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            
            <div class="col-md-4">
              <label class="form-label" for="contact_phone">{{ __('Contact Phone') }}</label>
              <input type="text" class="form-control @error('contact_phone') is-invalid @enderror" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $warehouse->contact_phone) }}" />
              @error('contact_phone')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
          
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $warehouse->is_active ? 'checked' : '' }} />
              <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
            </div>
          </div>
          
          <hr class="my-4">
          
          <!-- Warehouse Zones Section -->
          <h6 class="mb-3">{{ __('Warehouse Zones') }}</h6>
          <div class="row mb-3">
            <div class="col-12">
              <div class="table-responsive">
                <table class="table table-bordered" id="zones-table">
                  <thead>
                    <tr>
                      <th>{{ __('Zone Name') }}</th>
                      <th>{{ __('Zone Code') }}</th>
                      <th>{{ __('Description') }}</th>
                      <th>{{ __('Actions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($warehouse->zones as $index => $zone)
                    <tr class="zone-row">
                      <td>
                        <input type="hidden" name="zones[{{ $index }}][id]" value="{{ $zone->id }}">
                        <input type="text" class="form-control" name="zones[{{ $index }}][name]" value="{{ $zone->name }}" required>
                      </td>
                      <td>
                        <input type="text" class="form-control" name="zones[{{ $index }}][code]" value="{{ $zone->code }}" required>
                      </td>
                      <td>
                        <input type="text" class="form-control" name="zones[{{ $index }}][description]" value="{{ $zone->description }}">
                      </td>
                      <td>
                        <button type="button" class="btn btn-danger btn-sm remove-zone-row" {{ $warehouse->zones->count() <= 1 ? 'disabled' : '' }}>
                          <i class="bx bx-trash"></i>
                        </button>
                      </td>
                    </tr>
                    @empty
                    <tr class="zone-row">
                      <td>
                        <input type="text" class="form-control" name="zones[0][name]" required>
                      </td>
                      <td>
                        <input type="text" class="form-control" name="zones[0][code]" required>
                      </td>
                      <td>
                        <input type="text" class="form-control" name="zones[0][description]">
                      </td>
                      <td>
                        <button type="button" class="btn btn-danger btn-sm remove-zone-row" disabled>
                          <i class="bx bx-trash"></i>
                        </button>
                      </td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <button type="button" class="btn btn-secondary btn-sm mt-2" id="add-zone-row">
                <i class="bx bx-plus me-1"></i> {{ __('Add Zone') }}
              </button>
            </div>
          </div>
          
          <div class="mt-4">
            <button type="submit" class="btn btn-primary me-2">{{ __('Update Warehouse') }}</button>
            <a href="{{ route('wmsinventorycore.warehouses.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
