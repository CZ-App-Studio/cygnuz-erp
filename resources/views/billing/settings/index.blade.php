{{-- Billing Settings Template --}}
@extends('layouts.layoutMaster')

@section('title', __('Billing Settings'))

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss'
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app/billing-settings.js'])
@endsection

@section('content')
<x-breadcrumb 
    :title="__('Billing Settings')" 
    :items="[
        ['label' => __('Settings'), 'url' => route('settings.index')],
        ['label' => __('Billing Settings')]
    ]" />

@can('billing.manage-billing-settings')
<form id="billingSettingsForm" action="{{ route('billing.settings.update') }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="row">
        <!-- General Billing Settings -->
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-cog me-2"></i>{{ __('General Settings') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="default_currency" class="form-label">{{ __('Default Currency') }}</label>
                            <select class="form-select" id="default_currency" name="default_currency">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="CAD">CAD - Canadian Dollar</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="default_tax_rate" class="form-label">{{ __('Default Tax Rate (%)') }}</label>
                            <input type="number" class="form-control" id="default_tax_rate" name="default_tax_rate" step="0.01" min="0" max="100">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="invoice_prefix" class="form-label">{{ __('Invoice Number Prefix') }}</label>
                            <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" placeholder="INV-">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="proposal_prefix" class="form-label">{{ __('Proposal Number Prefix') }}</label>
                            <input type="text" class="form-control" id="proposal_prefix" name="proposal_prefix" placeholder="PROP-">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="default_payment_terms" class="form-label">{{ __('Default Payment Terms (Days)') }}</label>
                            <select class="form-select" id="default_payment_terms" name="default_payment_terms">
                                <option value="15">{{ __('Net 15') }}</option>
                                <option value="30">{{ __('Net 30') }}</option>
                                <option value="45">{{ __('Net 45') }}</option>
                                <option value="60">{{ __('Net 60') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="late_fee_percentage" class="form-label">{{ __('Late Fee Percentage (%)') }}</label>
                            <input type="number" class="form-control" id="late_fee_percentage" name="late_fee_percentage" step="0.01" min="0" max="50">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Email Templates Settings -->
        @can('billing.manage-email-templates')
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-envelope me-2"></i>{{ __('Email Templates') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="invoice_email_subject" class="form-label">{{ __('Invoice Email Subject') }}</label>
                        <input type="text" class="form-control" id="invoice_email_subject" name="invoice_email_subject" 
                               placeholder="Invoice #{invoice_number} from {company_name}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="proposal_email_subject" class="form-label">{{ __('Proposal Email Subject') }}</label>
                        <input type="text" class="form-control" id="proposal_email_subject" name="proposal_email_subject" 
                               placeholder="Proposal #{proposal_number} from {company_name}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_reminder_subject" class="form-label">{{ __('Payment Reminder Subject') }}</label>
                        <input type="text" class="form-control" id="payment_reminder_subject" name="payment_reminder_subject" 
                               placeholder="Payment Reminder - Invoice #{invoice_number}">
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <!-- Payment Settings -->
        @can('billing.manage-payment-settings')
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-credit-card me-2"></i>{{ __('Payment Settings') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enable_online_payments" name="enable_online_payments">
                                <label class="form-check-label" for="enable_online_payments">
                                    {{ __('Enable Online Payments') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_send_reminders" name="auto_send_reminders">
                                <label class="form-check-label" for="auto_send_reminders">
                                    {{ __('Auto Send Payment Reminders') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="accepted_payment_methods" class="form-label">{{ __('Accepted Payment Methods') }}</label>
                            <select class="form-select" id="accepted_payment_methods" name="accepted_payment_methods[]" multiple>
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="check">{{ __('Check') }}</option>
                                <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                <option value="credit_card">{{ __('Credit Card') }}</option>
                                <option value="paypal">{{ __('PayPal') }}</option>
                                <option value="stripe">{{ __('Stripe') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <!-- Automation Settings -->
        @can('billing.manage-automation-settings')
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bx bx-bot me-2"></i>{{ __('Automation Settings') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_send_invoices" name="auto_send_invoices">
                                <label class="form-check-label" for="auto_send_invoices">
                                    {{ __('Auto Send Invoices') }}
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="auto_mark_overdue" name="auto_mark_overdue">
                                <label class="form-check-label" for="auto_mark_overdue">
                                    {{ __('Auto Mark Overdue Invoices') }}
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reminder_schedule" class="form-label">{{ __('Reminder Schedule (Days Before Due)') }}</label>
                            <input type="text" class="form-control" id="reminder_schedule" name="reminder_schedule" 
                                   placeholder="7,3,1" help="Comma-separated days">
                            <div class="form-text">{{ __('Example: 7,3,1 sends reminders 7, 3, and 1 days before due date') }}</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="overdue_schedule" class="form-label">{{ __('Overdue Follow-up Schedule (Days After Due)') }}</label>
                            <input type="text" class="form-control" id="overdue_schedule" name="overdue_schedule" 
                                   placeholder="1,7,14,30">
                            <div class="form-text">{{ __('Example: 1,7,14,30 sends follow-ups 1, 7, 14, and 30 days after due date') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <!-- Save Actions -->
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('settings.index') }}" class="btn btn-label-secondary">
                                <i class="bx bx-arrow-back me-1"></i>{{ __('Back to Settings') }}
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            @can('billing.reset-billing-settings')
                                <button type="button" class="btn btn-label-danger" onclick="resetToDefaults()">
                                    <i class="bx bx-reset me-1"></i>{{ __('Reset to Defaults') }}
                                </button>
                            @endcan
                            
                            @can('billing.manage-billing-settings')
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-check me-1"></i>{{ __('Save Settings') }}
                                </button>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@else
{{-- No Permission Message --}}
<div class="col-12">
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bx bx-lock-alt bx-lg text-muted mb-3"></i>
            <h4 class="text-muted">{{ __('Access Denied') }}</h4>
            <p class="text-muted">{{ __('You do not have permission to manage billing settings.') }}</p>
            <a href="{{ route('settings.index') }}" class="btn btn-primary">
                <i class="bx bx-arrow-back me-1"></i>{{ __('Back to Settings') }}
            </a>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script>
function resetToDefaults() {
    @can('billing.reset-billing-settings')
        Swal.fire({
            title: '{{ __('Reset to Defaults?') }}',
            text: '{{ __('This will reset all billing settings to their default values. This action cannot be undone.') }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '{{ __('Yes, reset!') }}',
            cancelButtonText: '{{ __('Cancel') }}',
            customClass: {
                confirmButton: 'btn btn-danger'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route('billing.settings.reset') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).then(response => {
                    if (response.ok) {
                        Swal.fire({
                            title: '{{ __('Reset Complete!') }}',
                            text: '{{ __('Billing settings have been reset to defaults.') }}',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('{{ __('Error') }}', '{{ __('Failed to reset settings.') }}', 'error');
                    }
                });
            }
        });
    @else
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to reset billing settings.') }}', 'error');
    @endcan
}

// Form validation and submission
document.getElementById('billingSettingsForm').addEventListener('submit', function(e) {
    @cannot('billing.manage-billing-settings')
        e.preventDefault();
        Swal.fire('{{ __('Access Denied') }}', '{{ __('You do not have permission to save billing settings.') }}', 'error');
        return false;
    @endcan
    
    // Additional validation can be added here
});
</script>
@endpush