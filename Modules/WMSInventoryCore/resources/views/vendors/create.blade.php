@extends('layouts.layoutMaster')

@section('title', __('Create Vendor'))

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
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-vendor-form.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Vendors'), 'url' => route('wmsinventorycore.vendors.index')],
    ['name' => __('Create'), 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Create Vendor')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<form id="vendorForm" action="{{ route('wmsinventorycore.vendors.store') }}" method="POST">
  @csrf
  
  <div class="row">
    {{-- Basic Information Card --}}
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Basic Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="name" class="form-label">{{ __('Vendor Name') }} <span class="text-danger">*</span></label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="company_name" class="form-label">{{ __('Company Name') }}</label>
            <input type="text" class="form-control @error('company_name') is-invalid @enderror" id="company_name" name="company_name" value="{{ old('company_name') }}">
            @error('company_name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="status" class="form-label">{{ __('Status') }} <span class="text-danger">*</span></label>
            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
              <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>{{ __('Active') }}</option>
              <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
            </select>
            @error('status')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="tax_number" class="form-label">{{ __('Tax Number') }}</label>
            <input type="text" class="form-control @error('tax_number') is-invalid @enderror" id="tax_number" name="tax_number" value="{{ old('tax_number') }}">
            @error('tax_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- Contact Information Card --}}
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Contact Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email') }}</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="phone_number" class="form-label">{{ __('Phone Number') }}</label>
            <input type="text" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number') }}">
            @error('phone_number')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="website" class="form-label">{{ __('Website') }}</label>
            <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website') }}" placeholder="https://example.com">
            @error('website')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>

    {{-- Address Information Card --}}
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Address Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="address" class="form-label">{{ __('Address') }}</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address') }}</textarea>
            @error('address')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="city" class="form-label">{{ __('City') }}</label>
              <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}">
              @error('city')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label for="state" class="form-label">{{ __('State/Province') }}</label>
              <input type="text" class="form-control @error('state') is-invalid @enderror" id="state" name="state" value="{{ old('state') }}">
              @error('state')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="country" class="form-label">{{ __('Country') }}</label>
              <input type="text" class="form-control @error('country') is-invalid @enderror" id="country" name="country" value="{{ old('country') }}">
              @error('country')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label for="postal_code" class="form-label">{{ __('Postal Code') }}</label>
              <input type="text" class="form-control @error('postal_code') is-invalid @enderror" id="postal_code" name="postal_code" value="{{ old('postal_code') }}">
              @error('postal_code')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Business Terms Card --}}
    <div class="col-md-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Business Terms') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="payment_terms" class="form-label">{{ __('Payment Terms') }}</label>
            <select class="form-select select2 @error('payment_terms') is-invalid @enderror" id="payment_terms" name="payment_terms">
              <option value="">{{ __('Select Payment Terms') }}</option>
              <option value="COD" {{ old('payment_terms') == 'COD' ? 'selected' : '' }}>{{ __('Cash on Delivery (COD)') }}</option>
              <option value="Net 15" {{ old('payment_terms') == 'Net 15' ? 'selected' : '' }}>{{ __('Net 15') }}</option>
              <option value="Net 30" {{ old('payment_terms') == 'Net 30' ? 'selected' : '' }}>{{ __('Net 30') }}</option>
              <option value="Net 45" {{ old('payment_terms') == 'Net 45' ? 'selected' : '' }}>{{ __('Net 45') }}</option>
              <option value="Net 60" {{ old('payment_terms') == 'Net 60' ? 'selected' : '' }}>{{ __('Net 60') }}</option>
              <option value="Due on Receipt" {{ old('payment_terms') == 'Due on Receipt' ? 'selected' : '' }}>{{ __('Due on Receipt') }}</option>
              <option value="2/10 Net 30" {{ old('payment_terms') == '2/10 Net 30' ? 'selected' : '' }}>{{ __('2/10 Net 30') }}</option>
            </select>
            @error('payment_terms')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="lead_time_days" class="form-label">{{ __('Lead Time (Days)') }}</label>
              <input type="number" class="form-control @error('lead_time_days') is-invalid @enderror" id="lead_time_days" name="lead_time_days" value="{{ old('lead_time_days') }}" min="0">
              @error('lead_time_days')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6 mb-3">
              <label for="minimum_order_value" class="form-label">{{ __('Minimum Order Value') }}</label>
              <div class="input-group">
                <span class="input-group-text">{{ config('app.currency_symbol', '$') }}</span>
                <input type="number" class="form-control @error('minimum_order_value') is-invalid @enderror" id="minimum_order_value" name="minimum_order_value" value="{{ old('minimum_order_value') }}" min="0" step="0.01">
                @error('minimum_order_value')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Additional Information Card --}}
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Additional Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="notes" class="form-label">{{ __('Notes') }}</label>
            <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4" placeholder="{{ __('Enter any additional notes about this vendor...') }}">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Form Actions --}}
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('wmsinventorycore.vendors.index') }}" class="btn btn-label-secondary">
              <i class="bx bx-x me-2"></i>{{ __('Cancel') }}
            </a>
            <button type="submit" class="btn btn-primary">
              <i class="bx bx-save me-2"></i>{{ __('Save Vendor') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection