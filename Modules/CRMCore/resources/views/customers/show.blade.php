@extends('layouts.layoutMaster')

@section('title', __('Customer Details'))

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('CRM'), 'url' => route('crm.dashboard.index')],
    ['name' => __('Customers'), 'url' => route('customers.index')],
    ['name' => $customer->display_name, 'url' => '']
  ];
@endphp

<x-breadcrumb
  :title="__('Customer Details')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
    <!-- Customer Overview -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar avatar-xl mx-auto mb-3">
                    <span class="avatar-initial rounded-circle bg-label-primary">
                        {{ substr($customer->display_name, 0, 2) }}
                    </span>
                </div>
                <h4 class="mb-1">{{ $customer->display_name }}</h4>
                <p class="text-muted mb-1">{{ $customer->customer_code }}</p>

                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-{{ $customer->customer_type == 'vip' ? 'warning' : ($customer->customer_type == 'corporate' ? 'dark' : 'primary') }}">
                        {{ ucfirst($customer->customer_type) }}
                    </span>
                    @if($customer->customerGroup)
                        <span class="badge bg-info">{{ $customer->customerGroup->name }}</span>
                    @endif
                    @if($customer->is_blacklisted)
                        <span class="badge bg-danger">{{ __('Blacklisted') }}</span>
                    @elseif($customer->is_active)
                        <span class="badge bg-success">{{ __('Active') }}</span>
                    @else
                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                    @endif
                </div>

                <div class="d-flex justify-content-center gap-2">
                    <a href="{{ route('customers.edit', $customer->id) }}" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i> {{ __('Edit') }}
                    </a>
                    @if($customer->is_blacklisted)
                        <button type="button" class="btn btn-success" onclick="toggleBlacklist({{ $customer->id }}, false)">
                            <i class="bx bx-check-circle me-1"></i> {{ __('Remove Blacklist') }}
                        </button>
                    @else
                        <button type="button" class="btn btn-danger" onclick="toggleBlacklist({{ $customer->id }}, true)">
                            <i class="bx bx-block me-1"></i> {{ __('Blacklist') }}
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Contact Information') }}</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">{{ __('Email') }}:</dt>
                    <dd class="col-sm-7">{{ $customer->email ?: '-' }}</dd>

                    <dt class="col-sm-5">{{ __('Phone') }}:</dt>
                    <dd class="col-sm-7">{{ $customer->phone ?: '-' }}</dd>

                    @if($customer->contact->company)
                        <dt class="col-sm-5">{{ __('Company') }}:</dt>
                        <dd class="col-sm-7">{{ $customer->contact->company->name }}</dd>
                    @endif

                    @if($customer->contact->job_title)
                        <dt class="col-sm-5">{{ __('Job Title') }}:</dt>
                        <dd class="col-sm-7">{{ $customer->contact->job_title }}</dd>
                    @endif

                    <dt class="col-sm-5">{{ __('Address') }}:</dt>
                    <dd class="col-sm-7">{{ $customer->address ?: '-' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <!-- Customer Details -->
    <div class="col-md-8">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">{{ __('Total Purchases') }}</p>
                                <h3 class="mb-0 fw-bold">{{ $customer->purchase_count }}</h3>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-primary rounded">
                                    <i class="bx bx-shopping-bag bx-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">{{ __('Lifetime Value') }}</p>
                                <h3 class="mb-0 fw-bold">${{ number_format($customer->lifetime_value, 2) }}</h3>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-success rounded">
                                    <i class="bx bx-dollar bx-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">{{ __('Avg Order') }}</p>
                                <h3 class="mb-0 fw-bold">${{ number_format($customer->average_order_value, 2) }}</h3>
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
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">{{ __('Available Credit') }}</p>
                                <h3 class="mb-0 fw-bold">${{ number_format($customer->available_credit, 2) }}</h3>
                            </div>
                            <div class="avatar">
                                <div class="avatar-initial bg-label-warning rounded">
                                    <i class="bx bx-credit-card bx-sm"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Details Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#details-tab">
                            {{ __('Details') }}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#transactions-tab">
                            {{ __('Recent Transactions') }}
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#notes-tab">
                            {{ __('Notes') }}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Details Tab -->
                    <div class="tab-pane fade show active" id="details-tab">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">{{ __('Customer Since') }}:</dt>
                                    <dd class="col-sm-7">{{ $customer->created_at->format('d M Y') }}</dd>

                                    <dt class="col-sm-5">{{ __('First Purchase') }}:</dt>
                                    <dd class="col-sm-7">
                                        {{ $customer->first_purchase_date ? $customer->first_purchase_date->format('d M Y') : __('No purchases yet') }}
                                    </dd>

                                    <dt class="col-sm-5">{{ __('Last Purchase') }}:</dt>
                                    <dd class="col-sm-7">
                                        {{ $customer->last_purchase_date ? $customer->last_purchase_date->format('d M Y') : __('No purchases yet') }}
                                    </dd>

                                    <dt class="col-sm-5">{{ __('Payment Terms') }}:</dt>
                                    <dd class="col-sm-7">
                                        @switch($customer->payment_terms)
                                            @case('cod')
                                                {{ __('Cash on Delivery') }}
                                                @break
                                            @case('prepaid')
                                                {{ __('Prepaid') }}
                                                @break
                                            @case('net30')
                                                {{ __('Net 30 Days') }}
                                                @break
                                            @case('net60')
                                                {{ __('Net 60 Days') }}
                                                @break
                                        @endswitch
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">{{ __('Credit Limit') }}:</dt>
                                    <dd class="col-sm-7">${{ number_format($customer->credit_limit, 2) }}</dd>

                                    <dt class="col-sm-5">{{ __('Credit Used') }}:</dt>
                                    <dd class="col-sm-7">${{ number_format($customer->credit_used, 2) }}</dd>

                                    <dt class="col-sm-5">{{ __('Discount') }}:</dt>
                                    <dd class="col-sm-7">{{ $customer->discount_percentage }}%</dd>

                                    <dt class="col-sm-5">{{ __('Tax Status') }}:</dt>
                                    <dd class="col-sm-7">
                                        @if($customer->tax_exempt)
                                            <span class="badge bg-info">{{ __('Tax Exempt') }}</span>
                                            @if($customer->tax_number)
                                                <br><small>{{ __('Tax #') }}: {{ $customer->tax_number }}</small>
                                            @endif
                                        @else
                                            {{ __('Standard Tax') }}
                                        @endif
                                    </dd>

                                    @if($customer->business_registration)
                                        <dt class="col-sm-5">{{ __('Business Reg') }}:</dt>
                                        <dd class="col-sm-7">{{ $customer->business_registration }}</dd>
                                    @endif
                                </dl>
                            </div>
                        </div>

                        @if($customer->is_blacklisted && $customer->blacklist_reason)
                            <div class="alert alert-danger mt-3">
                                <h6 class="alert-heading">{{ __('Blacklist Reason') }}</h6>
                                <p class="mb-0">{{ $customer->blacklist_reason }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Transactions Tab -->
                    <div class="tab-pane fade" id="transactions-tab">
                        @if(count($recentTransactions) > 0)
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Invoice #') }}</th>
                                            <th>{{ __('Amount') }}</th>
                                            <th>{{ __('Status') }}</th>
                                            <th>{{ __('Actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($recentTransactions as $transaction)
                                            <!-- Transaction rows will be populated when sales module is ready -->
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="bx bx-receipt bx-lg text-muted"></i>
                                </div>
                                <p class="text-muted">{{ __('No transactions found') }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Notes Tab -->
                    <div class="tab-pane fade" id="notes-tab">
                        @if($customer->notes)
                            <p>{{ $customer->notes }}</p>
                        @else
                            <p class="text-muted">{{ __('No notes available') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Blacklist Modal -->
<div class="modal fade" id="blacklistModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blacklistModalTitle">{{ __('Add to Blacklist') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="blacklistForm">
                <div class="modal-body">
                    <input type="hidden" id="blacklist-action" value="true">

                    <div class="mb-3" id="blacklist-reason-group">
                        <label class="form-label">{{ __('Reason for Blacklisting') }} *</label>
                        <textarea class="form-control" id="blacklist-reason" rows="3" required></textarea>
                    </div>

                    <div class="alert alert-warning" id="blacklist-warning">
                        <i class="bx bx-info-circle me-1"></i>
                        {{ __('Blacklisted customers will not be able to make purchases.') }}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Confirm') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/toastr/toastr.js'
])
@endsection

@section('page-script')
<script>
// Page data for JavaScript
const pageData = {
    urls: {
        blacklist: @json(route('customers.blacklist', $customer->id))
    },
    labels: {
        addToBlacklist: @json(__('Add to Blacklist')),
        removeFromBlacklist: @json(__('Remove from Blacklist')),
        errorOccurred: @json(__('An error occurred'))
    }
};
</script>
@vite(['Modules/CRMCore/resources/assets/js/customers-show.js'])
@endsection
