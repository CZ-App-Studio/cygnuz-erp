@extends('layouts.layoutMaster')

@section('title', __('Tax Rates'))

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
@endsection

@section('content')
<x-breadcrumb 
    :title="__('Tax Rates')"
    :homeRoute="route('accountingcore.index')" 
    :breadcrumbs="[
        ['name' => __('Accounting'), 'url' => route('accountingcore.index')],
        ['name' => __('Tax Rates'), 'url' => route('accountingcore.tax-rates.index')]
    ]"
/>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Tax Rates') }}</h5>
        @can('accountingcore.tax-rates.store')
            <button type="button" class="btn btn-primary" onclick="createTaxRate()">
                <i class="bx bx-plus me-1"></i> {{ __('Add Tax Rate') }}
            </button>
        @endcan
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="taxRatesTable">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Rate') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Tax Authority') }}</th>
                        <th>{{ __('Default') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Tax Rate Form Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="taxRateOffcanvas" aria-labelledby="taxRateOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 id="taxRateOffcanvasLabel">{{ __('Add Tax Rate') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="taxRateForm">
            @csrf
            <input type="hidden" id="taxRateId" name="id">
            
            <div class="mb-3">
                <label for="name" class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required>
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="rate" class="form-label">{{ __('Rate (%)') }} <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="rate" name="rate" step="0.0001" min="0" max="100" required>
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
                <select class="form-select" id="type" name="type" required>
                    <option value="percentage">{{ __('Percentage') }}</option>
                    <option value="fixed">{{ __('Fixed Amount') }}</option>
                </select>
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="tax_authority" class="form-label">{{ __('Tax Authority') }}</label>
                <input type="text" class="form-control" id="tax_authority" name="tax_authority" placeholder="{{ __('e.g., Federal, State, GST, VAT') }}">
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">{{ __('Description') }}</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                <div class="invalid-feedback"></div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                    <label class="form-check-label" for="is_default">
                        {{ __('Set as Default') }}
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
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
const pageData = {
    urls: {
        datatable: '{{ route("accountingcore.tax-rates.datatable") }}',
        store: '{{ route("accountingcore.tax-rates.store") }}',
        show: '{{ route("accountingcore.tax-rates.index") }}',
        update: '{{ route("accountingcore.tax-rates.index") }}',
        destroy: '{{ route("accountingcore.tax-rates.index") }}'
    },
    labels: {
        addTaxRate: @json(__('Add Tax Rate')),
        editTaxRate: @json(__('Edit Tax Rate')),
        confirmDelete: @json(__('Are you sure you want to delete this tax rate?')),
        yesDelete: @json(__('Yes, delete it!')),
        cancel: @json(__('Cancel')),
        deleteSuccess: @json(__('Tax rate deleted successfully')),
        deleteError: @json(__('Failed to delete tax rate')),
        success: @json(__('Success')),
        error: @json(__('Error')),
        genericError: @json(__('An error occurred')),
        requestError: @json(__('An error occurred while processing your request'))
    }
};
</script>
@vite(['Modules/AccountingCore/resources/assets/js/tax-rates.js'])
@endsection