@extends('layouts.layoutMaster')

@section('title', __('Create Customer'))

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
    ['name' => __('Create'), 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Create Customer')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <form id="customerForm" action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Contact Selection -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Select Contact') }} *</label>
                            <select class="form-select" id="contact_id" name="contact_id" required>
                                <option value="">{{ __('Select a contact') }}</option>
                                @foreach($contacts as $contact)
                                    <option value="{{ $contact->id }}">
                                        {{ $contact->getFullNameAttribute() }}
                                        ({{ $contact->email_primary ?: $contact->phone_primary ?: __('No contact info') }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Select an existing contact to convert to customer') }}</div>
                        </div>

                        <!-- Customer Group -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Customer Group') }}</label>
                            <select class="form-select" name="customer_group_id">
                                <option value="">{{ __('None') }}</option>
                                @foreach($customerGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Payment Terms -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Payment Terms') }} *</label>
                            <select class="form-select" name="payment_terms" required>
                                <option value="cod" selected>{{ __('Cash on Delivery') }}</option>
                                <option value="prepaid">{{ __('Prepaid') }}</option>
                                <option value="net30">{{ __('Net 30 Days') }}</option>
                                <option value="net60">{{ __('Net 60 Days') }}</option>
                            </select>
                        </div>

                        <!-- Credit Limit -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Credit Limit') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" name="credit_limit" value="0" min="0" step="0.01">
                            </div>
                        </div>

                        <!-- Discount Percentage -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Discount Percentage') }}</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="discount_percentage" value="0" min="0" max="100" step="0.01">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <!-- Tax Exempt -->
                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" id="tax_exempt" name="tax_exempt" value="1">
                                <label class="form-check-label" for="tax_exempt">
                                    {{ __('Tax Exempt') }}
                                </label>
                            </div>
                        </div>

                        <!-- Tax Number -->
                        <div class="col-md-6 mb-3" id="tax-number-group" style="display: none;">
                            <label class="form-label">{{ __('Tax Number') }}</label>
                            <input type="text" class="form-control" name="tax_number" maxlength="255">
                        </div>

                        <!-- Business Registration -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Business Registration') }}</label>
                            <input type="text" class="form-control" name="business_registration" maxlength="255">
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('Notes') }}</label>
                            <textarea class="form-control" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i> {{ __('Create Customer') }}
                    </button>
                    <a href="{{ route('customers.index') }}" class="btn btn-label-secondary">
                        <i class="bx bx-x me-1"></i> {{ __('Cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Contact Info Display -->
<div class="row mt-4" id="contact-info-display" style="display: none;">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Contact Information') }}</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('Name') }}:</dt>
                            <dd class="col-sm-8" id="contact-name">-</dd>

                            <dt class="col-sm-4">{{ __('Email') }}:</dt>
                            <dd class="col-sm-8" id="contact-email">-</dd>

                            <dt class="col-sm-4">{{ __('Phone') }}:</dt>
                            <dd class="col-sm-8" id="contact-phone">-</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">{{ __('Company') }}:</dt>
                            <dd class="col-sm-8" id="contact-company">-</dd>

                            <dt class="col-sm-4">{{ __('Job Title') }}:</dt>
                            <dd class="col-sm-8" id="contact-job-title">-</dd>

                            <dt class="col-sm-4">{{ __('Address') }}:</dt>
                            <dd class="col-sm-8" id="contact-address">-</dd>
                        </dl>
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
    urls: {
        searchContacts: @json(route('customers.search.contacts'))
    },
    labels: {
        searchContact: @json(__('Search for a contact')),
        errorOccurred: @json(__('An error occurred'))
    },
    contactsData: {
        @foreach($contacts as $contact)
        @php
            $addressParts = array_filter([
                $contact->address_street,
                $contact->address_city,
                $contact->address_state,
                $contact->address_postal_code,
                $contact->address_country
            ]);
            $address = implode(', ', $addressParts) ?: '-';
        @endphp
        '{{ $contact->id }}': {
            name: @json($contact->getFullNameAttribute()),
            email: @json($contact->email_primary ?: '-'),
            phone: @json($contact->phone_primary ?: $contact->phone_mobile ?: '-'),
            company: @json($contact->company ? $contact->company->name : '-'),
            job_title: @json($contact->job_title ?: '-'),
            address: @json($address)
        },
        @endforeach
    }
};
</script>
@vite(['Modules/CRMCore/resources/assets/js/customers-create.js'])
@endsection
