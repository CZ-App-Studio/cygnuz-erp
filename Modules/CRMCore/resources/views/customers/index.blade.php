@extends('layouts.layoutMaster')

@section('title', __('Customer Management'))

@section('vendor-style')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
      'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
      'resources/assets/vendor/libs/select2/select2.scss',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
      'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
      'resources/assets/vendor/libs/select2/select2.js',
      'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Customers'), 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Customer Management')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<!-- Statistics Cards -->
<div class="row mb-4" id="statistics-cards">
    <div class="col-md-2 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">{{ __('Total Customers') }}</h6>
                        <h3 class="mb-0 fw-bold text-primary" id="stat-total">0</h3>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded">
                            <i class="bx bx-group bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">{{ __('Active') }}</h6>
                        <h3 class="mb-0 fw-bold text-success" id="stat-active">0</h3>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-success rounded">
                            <i class="bx bx-check-circle bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">{{ __('New This Month') }}</h6>
                        <h3 class="mb-0 fw-bold text-info" id="stat-new">0</h3>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-info rounded">
                            <i class="bx bx-trending-up bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">{{ __('With Groups') }}</h6>
                        <h3 class="mb-0 fw-bold text-warning" id="stat-groups">0</h3>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-warning rounded">
                            <i class="bx bx-star bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">{{ __('Total Value') }}</h6>
                        <h4 class="mb-0 fw-bold text-primary" id="stat-value">0</h4>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-primary rounded">
                            <i class="bx bx-dollar bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-sm-6 mb-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-muted">{{ __('Blacklisted') }}</h6>
                        <h3 class="mb-0 fw-bold text-danger" id="stat-blacklisted">0</h3>
                    </div>
                    <div class="avatar">
                        <div class="avatar-initial bg-label-danger rounded">
                            <i class="bx bx-block bx-sm"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ __('Filters') }}</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">{{ __('Customer Type') }}</label>
                <select class="form-select" id="filter-customer-type">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="regular">{{ __('Regular') }}</option>
                    <option value="vip">{{ __('VIP') }}</option>
                    <option value="wholesale">{{ __('Wholesale') }}</option>
                    <option value="corporate">{{ __('Corporate') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('Customer Group') }}</label>
                <select class="form-select" id="filter-customer-group">
                    <option value="">{{ __('All Groups') }}</option>
                    @foreach($customerGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ __('Status') }}</label>
                <select class="form-select" id="filter-status">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="1">{{ __('Active') }}</option>
                    <option value="0">{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-secondary w-100" onclick="resetFilters()">
                    <i class="bx bx-reset me-1"></i> {{ __('Reset Filters') }}
                </button>
            </div>
        </div>

    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('All Customers') }}</h5>
            <a href="{{ route('customers.create') }}" class="btn btn-primary">
                <i class="bx bx-plus"></i> {{ __('Add New Customer') }}
            </a>
        </div>
    </div>
    <div class="card-datatable table-responsive">
        <table class="table table-bordered" id="customers-table">
                <thead>
                    <tr>
                        <th>{{ __('Customer') }}</th>
                        <th>{{ __('Contact Info') }}</th>
                        <th>{{ __('Group') }}</th>
                        <th>{{ __('Lifetime Value') }}</th>
                        <th>{{ __('Last Purchase') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
  const pageData = {
    urls: {
      datatable: @json(route('customers.datatable')),
      statistics: @json(route('customers.statistics')),
      show: @json(route('customers.show', ':id')),
      edit: @json(route('customers.edit', ':id')),
      blacklist: @json(route('customers.blacklist', ':id'))
    },
    labels: {
      never: @json(__('Never')),
      addToBlacklist: @json(__('Add to Blacklist')),
      removeFromBlacklist: @json(__('Remove from Blacklist')),
      reasonForBlacklisting: @json(__('Reason for Blacklisting')),
      enterReason: @json(__('Enter reason for blacklisting')),
      reasonRequired: @json(__('Reason is required')),
      blacklistWarning: @json(__('Blacklisted customers will not be able to make purchases.')),
      removeBlacklistWarning: @json(__('This will allow the customer to make purchases again.')),
      cancel: @json(__('Cancel')),
      success: @json(__('Success!')),
      error: @json(__('Error')),
      errorOccurred: @json(__('An error occurred'))
    }
  };
</script>
@vite(['Modules/CRMCore/resources/assets/js/customers.js'])
@endsection
