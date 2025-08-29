@extends('layouts.layoutMaster')

@section('title', __('Customer Groups'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/toastr/toastr.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/toastr/toastr.js'
  ])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Customer Groups'), 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Customer Groups')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<!-- Statistics -->
<div class="row mb-4">
  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="d-flex flex-column">
            <div class="card-title mb-auto">
              <h5 class="mb-1 text-nowrap">{{ __('Total Groups') }}</h5>
              <small>{{ __('All customer groups') }}</small>
            </div>
            <div class="chart-statistics">
              <h3 class="card-title mb-1" id="stat-total">0</h3>
            </div>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-primary rounded">
              <i class="bx bx-group bx-lg"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="d-flex flex-column">
            <div class="card-title mb-auto">
              <h5 class="mb-1 text-nowrap">{{ __('Active Groups') }}</h5>
              <small>{{ __('Currently active') }}</small>
            </div>
            <div class="chart-statistics">
              <h3 class="card-title mb-1" id="stat-active">0</h3>
            </div>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-success rounded">
              <i class="bx bx-check-circle bx-lg"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="d-flex flex-column">
            <div class="card-title mb-auto">
              <h5 class="mb-1 text-nowrap">{{ __('With Discounts') }}</h5>
              <small>{{ __('Groups offering discounts') }}</small>
            </div>
            <div class="chart-statistics">
              <h3 class="card-title mb-1" id="stat-discounts">0</h3>
            </div>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-warning rounded">
              <i class="bx bx-purchase-tag bx-lg"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-xl-3 col-lg-6 col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="d-flex flex-column">
            <div class="card-title mb-auto">
              <h5 class="mb-1 text-nowrap">{{ __('Total Customers') }}</h5>
              <small>{{ __('In all groups') }}</small>
            </div>
            <div class="chart-statistics">
              <h3 class="card-title mb-1" id="stat-customers">0</h3>
            </div>
          </div>
          <div class="avatar">
            <div class="avatar-initial bg-label-info rounded">
              <i class="bx bx-user bx-lg"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Customer Groups List -->
<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center row py-3 gap-3 gap-md-0">
      <div class="col-md-4">
        <h5 class="card-title mb-0">{{ __('Customer Groups') }}</h5>
      </div>
      <div class="col-md-8">
        <div class="d-flex align-items-center justify-content-md-end gap-3 flex-wrap">
          {{-- <div class="d-flex align-items-center gap-2">
            <label class="form-label mb-0">{{ __('Status') }}:</label>
            <select class="form-select" id="filter-status" style="min-width: 150px;">
              <option value="">{{ __('All') }}</option>
              <option value="1">{{ __('Active') }}</option>
              <option value="0">{{ __('Inactive') }}</option>
            </select>
          </div> --}}
          <button class="btn btn-primary" onclick="showCreateForm()">
            <i class="bx bx-plus me-1"></i> {{ __('Add Group') }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="table" id="customer-groups-table">
      <thead>
        <tr>
          <th>{{ __('Name') }}</th>
          <th>{{ __('Discount') }}</th>
          <th>{{ __('Priority') }}</th>
          <th>{{ __('Customers') }}</th>
          <th>{{ __('Status') }}</th>
          <th data-priority="1">{{ __('Actions') }}</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Right Offcanvas for Create/Edit -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCustomerGroup" aria-labelledby="offcanvasCustomerGroupLabel">
  <div class="offcanvas-header">
    <h5 id="offcanvasCustomerGroupLabel" class="offcanvas-title">{{ __('Add Customer Group') }}</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0">
    <form class="pt-0" id="customerGroupForm">
      <input type="hidden" id="group_id" name="id">

      <div class="mb-3">
        <label class="form-label" for="name">{{ __('Group Name') }} *</label>
        <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('Enter group name') }}" required />
      </div>

      <div class="mb-3">
        <label class="form-label" for="code">{{ __('Group Code') }} *</label>
        <input type="text" class="form-control text-uppercase" id="code" name="code" placeholder="{{ __('e.g. VIP, WHOLESALE') }}" required />
        <div class="form-text">{{ __('Unique code for this group') }}</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="description">{{ __('Description') }}</label>
        <textarea class="form-control" id="description" name="description" rows="3" placeholder="{{ __('Enter description') }}"></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label" for="discount_percentage">{{ __('Discount Percentage') }}</label>
        <div class="input-group">
          <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.01" placeholder="0" />
          <span class="input-group-text">%</span>
        </div>
        <div class="form-text">{{ __('Default discount for this group') }}</div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="priority">{{ __('Priority') }} *</label>
        <input type="number" class="form-control" id="priority" name="priority" min="0" value="0" required />
        <div class="form-text">{{ __('Lower number = higher priority') }}</div>
      </div>

      <div class="mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked value="1">
          <label class="form-check-label" for="is_active">
            {{ __('Active') }}
          </label>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary flex-fill">
          <i class="bx bx-save me-1"></i> {{ __('Save') }}
        </button>
        <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">
          {{ __('Cancel') }}
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@section('page-script')
<script>
// Page data for JavaScript
const pageData = {
    urls: {
        datatable: @json(route('customer-groups.datatable')),
        statistics: @json(route('customer-groups.statistics')),
        store: @json(route('customer-groups.store')),
        show: @json(route('customer-groups.show', ':id')),
        update: @json(route('customer-groups.update', ':id')),
        destroy: @json(route('customer-groups.destroy', ':id'))
    },
    labels: {
        addGroup: @json(__('Add Customer Group')),
        editGroup: @json(__('Edit Customer Group')),
        confirmDelete: @json(__('Are you sure?')),
        deleteWarning: @json(__('You will not be able to revert this!')),
        deleteButton: @json(__('Yes, delete it!')),
        cancelButton: @json(__('Cancel')),
        success: @json(__('Success!')),
        error: @json(__('Error')),
        errorOccurred: @json(__('An error occurred')),
        groupDeleted: @json(__('Customer group has been deleted.')),
        cannotDelete: @json(__('Cannot delete group with existing customers'))
    }
};
</script>
@vite(['Modules/CRMCore/resources/assets/js/customer-groups.js'])
@endsection
