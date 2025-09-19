@extends('layouts.layoutMaster')

@section('title', __('License Activation'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <h5 class="mb-0">
                    <i class="bx bx-key me-2"></i>{{ __('License Activation') }}
                </h5>
            </div>
            <div class="card-body">

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Main Application License -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="text-muted">{{ __('Main Application License') }}</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <form id="mainAppLicenseForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="customer_email" class="form-label">{{ __('Customer Email') }} *</label>
                                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                                value="{{ $customerEmail }}" required>
                                            <small class="form-text text-muted">{{ __('Use the email address from your purchase') }}</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="main_purchase_code" class="form-label">{{ __('Purchase Code') }}</label>
                                            <input type="text" class="form-control" id="main_purchase_code" name="purchase_code"
                                                placeholder="{{ __('Optional for main application') }}">
                                            <small class="form-text text-muted">{{ __('Leave empty if not required') }}</small>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-check-circle me-1"></i>{{ __('Activate Main Application') }}
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="validateMainApp">
                                        <i class="bx bx-search me-1"></i>{{ __('Validate License') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Addon Licenses -->
                @if(count($enabledAddons) > 0)
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-muted">{{ __('Addon Licenses') }}</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Addon Name') }}</th>
                                                <th>{{ __('Status') }}</th>
                                                <th>{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($enabledAddons as $addon)
                                            <tr data-addon="{{ $addon }}">
                                                <td>
                                                    <strong>{{ $addon }}</strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning addon-status" id="status-{{ $addon }}">
                                                        {{ __('Unknown') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary activate-addon" data-addon="{{ $addon }}">
                                                        <i class="bx bx-key me-1"></i>{{ __('Activate') }}
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary validate-addon" data-addon="{{ $addon }}">
                                                        <i class="bx bx-search me-1"></i>{{ __('Check Status') }}
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- License Information -->
                @if($licenseInfo && isset($licenseInfo['success']) && $licenseInfo['success'])
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="text-muted">{{ __('License Information') }}</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>{{ __('Customer Email') }}:</strong> {{ $customerEmail }}</p>
                                        <p><strong>{{ __('Domain') }}:</strong> {{ request()->getHost() }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>{{ __('Application Version') }}:</strong> {{ config('app.version', '1.0.0') }}</p>
                                        <p><strong>{{ __('Last Validated') }}:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

<!-- Addon Activation Modal -->
<div class="modal fade" id="addonActivationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Activate Addon') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addonActivationForm">
                <div class="modal-body">
                    <input type="hidden" id="addon_name" name="addon_name">
                    <div class="mb-3">
                        <label for="addon_customer_email" class="form-label">{{ __('Customer Email') }} *</label>
                        <input type="email" class="form-control" id="addon_customer_email" name="customer_email" 
                            value="{{ $customerEmail }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="addon_purchase_code" class="form-label">{{ __('Purchase Code') }} *</label>
                        <input type="text" class="form-control" id="addon_purchase_code" name="purchase_code" required
                            placeholder="{{ __('Enter your addon purchase code') }}">
                        <small class="form-text text-muted">{{ __('This is required for addon activation') }}</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-check-circle me-1"></i>{{ __('Activate Addon') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    // Pass data from Laravel to JavaScript
    window.pageData = {
        urls: {
            activateMainApp: '{{ route("license.activate-main-app") }}',
            activateAddon: '{{ route("license.activate-addon") }}',
            validateLicense: '{{ route("license.validate") }}',
            clearCache: '{{ route("license.clear-cache") }}'
        },
        labels: {
            success: @json(__('Success!')),
            error: @json(__('Error!')),
            processing: @json(__('Processing...')),
            activated: @json(__('Activated')),
            notActivated: @json(__('Not Activated')),
            validating: @json(__('Validating...')),
            activating: @json(__('Activating...'))
        }
    };

    window.hasCustomerEmail = {{ $customerEmail ? 'true' : 'false' }};
</script>
@vite(['resources/assets/js/app/license.js'])
@endsection