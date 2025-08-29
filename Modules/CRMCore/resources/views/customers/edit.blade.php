@extends('layouts.layoutMaster')

@section('title', __('Edit Customer'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Customers'), 'url' => route('customers.index')],
    ['name' => __('Edit'), 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Edit Customer')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <form id="customerForm" action="{{ route('customers.update', $customer->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <!-- Customer Info (Read-only) -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Customer Code') }}</label>
                            <input type="text" class="form-control" value="{{ $customer->customer_code }}" readonly>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Contact Name') }}</label>
                            <input type="text" class="form-control" value="{{ $customer->display_name }}" readonly>
                        </div>

                        <!-- Customer Group -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Customer Group') }}</label>
                            <select class="form-select" name="customer_group_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach($customerGroups as $group)
                                    <option value="{{ $group->id }}" {{ $customer->customer_group_id == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Terms -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Payment Terms') }} *</label>
                            <select class="form-select" name="payment_terms" required>
                                <option value="cod" {{ $customer->payment_terms == 'cod' ? 'selected' : '' }}>{{ __('Cash on Delivery') }}</option>
                                <option value="prepaid" {{ $customer->payment_terms == 'prepaid' ? 'selected' : '' }}>{{ __('Prepaid') }}</option>
                                <option value="net30" {{ $customer->payment_terms == 'net30' ? 'selected' : '' }}>{{ __('Net 30 Days') }}</option>
                                <option value="net60" {{ $customer->payment_terms == 'net60' ? 'selected' : '' }}>{{ __('Net 60 Days') }}</option>
                            </select>
                        </div>

                        <!-- Credit Limit -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Credit Limit') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="credit_limit" value="{{ $customer->credit_limit }}" min="0" step="0.01">
                            </div>
                            @if($customer->credit_used > 0)
                                <div class="form-text">{{ __('Currently Used') }}: ${{ number_format($customer->credit_used, 2) }}</div>
                            @endif
                        </div>

                        <!-- Discount Percentage -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Discount Percentage') }}</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="discount_percentage" value="{{ $customer->discount_percentage }}" min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $customer->is_active ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    {{ __('Active') }}
                                </label>
                            </div>
                        </div>

                        <!-- Tax Exempt -->
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="tax_exempt" name="tax_exempt" value="1" {{ $customer->tax_exempt ? 'checked' : '' }}>
                                <label class="form-check-label" for="tax_exempt">
                                    {{ __('Tax Exempt') }}
                                </label>
                            </div>
                        </div>

                        <!-- Tax Number -->
                        <div class="col-md-6 mb-3" id="tax-number-group" style="{{ $customer->tax_exempt ? '' : 'display: none;' }}">
                            <label class="form-label">{{ __('Tax Number') }}</label>
                            <input type="text" class="form-control" name="tax_number" value="{{ $customer->tax_number }}" maxlength="255">
                        </div>

                        <!-- Business Registration -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Business Registration') }}</label>
                            <input type="text" class="form-control" name="business_registration" value="{{ $customer->business_registration }}" maxlength="255">
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea class="form-control" name="notes" rows="3">{{ $customer->notes }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> {{ __('Update Customer') }}
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-label-secondary">
                        <i class="bx bx-x me-1"></i> {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Customer Statistics -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Customer Statistics') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">{{ $customer->purchase_count }}</h4>
                            <p class="text-muted mb-0">{{ __('Total Purchases') }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">${{ number_format($customer->lifetime_value, 2) }}</h4>
                            <p class="text-muted mb-0">{{ __('Lifetime Value') }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">${{ number_format($customer->average_order_value, 2) }}</h4>
                            <p class="text-muted mb-0">{{ __('Average Order') }}</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h4 class="mb-1">
                                @if($customer->last_purchase_date)
                                    {{ $customer->last_purchase_date->diffForHumans() }}
                                @else
                                    {{ __('Never') }}
                                @endif
                            </h4>
                            <p class="text-muted mb-0">{{ __('Last Purchase') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/toastr/toastr.js'
])
@endsection

@section('page-script')
<script>
// Page data for JavaScript
const pageData = {
    labels: {
        errorOccurred: @json(__('An error occurred'))
    }
};
</script>
@vite(['Modules/CRMCore/resources/assets/js/customers-edit.js'])
@endsection
