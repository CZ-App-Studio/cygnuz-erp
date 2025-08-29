@extends('layouts.layoutMaster')

@section('title', __('Invoices'))

@section('vendor-style')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
])
@endsection

@section('vendor-script')
@vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/js/main-datatable.js',
    'resources/assets/vendor/libs/select2/select2.js'
])
@endsection

@section('page-script')
@vite(['resources/assets/js/app/app-invoice-list.js'])
@endsection

@section('content')
<x-breadcrumb 
    :title="__('Invoices')" 
    :items="[
        ['label' => __('Billing'), 'url' => '#'],
        ['label' => __('Invoices')]
    ]" />

<div class="card">
    <div class="card-header border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Invoice Management') }}</h5>
            <div class="d-flex gap-2">
                @can('billing.invoices.create')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
                        <i class="bx bx-plus me-1"></i>{{ __('Create Invoice') }}
                    </button>
                @endcan
                @can('billing.invoices.export')
                    <button type="button" class="btn btn-label-info" onclick="exportInvoices()">
                        <i class="bx bx-export me-1"></i>{{ __('Export') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>
    
    <div class="card-datatable table-responsive">
        <table class="datatables-invoices table border-top">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('Invoice #') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Issue Date') }}</th>
                    <th>{{ __('Due Date') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td></td>
                    <td><strong>#{{ $invoice->invoice_number }}</strong></td>
                    <td>{{ $invoice->client_name }}</td>
                    <td>{{ $invoice->formatted_amount }}</td>
                    <td>
                        <span class="badge bg-label-{{ $invoice->status_color }}">
                            {{ $invoice->status_label }}
                        </span>
                    </td>
                    <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                    <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                                @can('billing.invoices.view')
                                    <a class="dropdown-item" href="{{ route('billing.invoices.show', $invoice->id) }}">
                                        <i class="bx bx-show-alt me-1"></i>{{ __('View') }}
                                    </a>
                                @endcan
                                @can('billing.invoices.edit')
                                    <a class="dropdown-item" href="{{ route('billing.invoices.edit', $invoice->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i>{{ __('Edit') }}
                                    </a>
                                @endcan
                                @can('billing.invoices.send')
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="sendInvoice({{ $invoice->id }})">
                                        <i class="bx bx-send me-1"></i>{{ __('Send') }}
                                    </a>
                                @endcan
                                @can('billing.invoices.duplicate')
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="duplicateInvoice({{ $invoice->id }})">
                                        <i class="bx bx-copy me-1"></i>{{ __('Duplicate') }}
                                    </a>
                                @endcan
                                @can('billing.invoices.print')
                                    <a class="dropdown-item" href="{{ route('billing.invoices.print', $invoice->id) }}" target="_blank">
                                        <i class="bx bx-printer me-1"></i>{{ __('Print') }}
                                    </a>
                                @endcan
                                @can('billing.invoices.delete')
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteInvoice({{ $invoice->id }})">
                                        <i class="bx bx-trash me-1"></i>{{ __('Delete') }}
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@can('billing.invoices.create')
<!-- Create Invoice Modal -->
<div class="modal fade" id="createInvoiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create New Invoice') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form id="createInvoiceForm" action="{{ route('billing.invoices.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Invoice form fields would go here -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">{{ __('Client') }}</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">{{ __('Select Client') }}</option>
                                <!-- Client options would be populated here -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="due_date" class="form-label">{{ __('Due Date') }}</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create Invoice') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection