@extends('layouts.layoutMaster')

@section('title', __('Proposals'))

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
@vite(['resources/assets/js/app/app-proposal-list.js'])
@endsection

@section('content')
<x-breadcrumb 
    :title="__('Proposals')" 
    :items="[
        ['label' => __('Billing'), 'url' => '#'],
        ['label' => __('Proposals')]
    ]" />

<div class="card">
    <div class="card-header border-bottom">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ __('Proposal Management') }}</h5>
            <div class="d-flex gap-2">
                @can('billing.proposals.create')
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProposalModal">
                        <i class="bx bx-plus me-1"></i>{{ __('Create Proposal') }}
                    </button>
                @endcan
                @can('billing.proposals.export')
                    <button type="button" class="btn btn-label-info" onclick="exportProposals()">
                        <i class="bx bx-export me-1"></i>{{ __('Export') }}
                    </button>
                @endcan
            </div>
        </div>
    </div>
    
    <div class="card-datatable table-responsive">
        <table class="datatables-proposals table border-top">
            <thead>
                <tr>
                    <th></th>
                    <th>{{ __('Proposal #') }}</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Amount') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created Date') }}</th>
                    <th>{{ __('Valid Until') }}</th>
                    <th>{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proposals as $proposal)
                <tr>
                    <td></td>
                    <td><strong>#{{ $proposal->proposal_number }}</strong></td>
                    <td>{{ $proposal->client_name }}</td>
                    <td>{{ $proposal->formatted_amount }}</td>
                    <td>
                        <span class="badge bg-label-{{ $proposal->status_color }}">
                            {{ $proposal->status_label }}
                        </span>
                    </td>
                    <td>{{ $proposal->created_at->format('M d, Y') }}</td>
                    <td>{{ $proposal->valid_until->format('M d, Y') }}</td>
                    <td>
                        <div class="dropdown">
                            <button type="button" class="btn p-0 dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <div class="dropdown-menu">
                                @can('billing.proposals.view')
                                    <a class="dropdown-item" href="{{ route('billing.proposals.show', $proposal->id) }}">
                                        <i class="bx bx-show-alt me-1"></i>{{ __('View') }}
                                    </a>
                                @endcan
                                @can('billing.proposals.edit')
                                    <a class="dropdown-item" href="{{ route('billing.proposals.edit', $proposal->id) }}">
                                        <i class="bx bx-edit-alt me-1"></i>{{ __('Edit') }}
                                    </a>
                                @endcan
                                @can('billing.proposals.send')
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="sendProposal({{ $proposal->id }})">
                                        <i class="bx bx-send me-1"></i>{{ __('Send') }}
                                    </a>
                                @endcan
                                @canany(['billing.proposals.accept', 'billing.proposals.reject'])
                                    <div class="dropdown-divider"></div>
                                    @can('billing.proposals.accept')
                                        <a class="dropdown-item text-success" href="javascript:void(0);" onclick="acceptProposal({{ $proposal->id }})">
                                            <i class="bx bx-check me-1"></i>{{ __('Accept') }}
                                        </a>
                                    @endcan
                                    @can('billing.proposals.reject')
                                        <a class="dropdown-item text-warning" href="javascript:void(0);" onclick="rejectProposal({{ $proposal->id }})">
                                            <i class="bx bx-x me-1"></i>{{ __('Reject') }}
                                        </a>
                                    @endcan
                                @endcanany
                                @can('billing.proposals.convert')
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="convertToInvoice({{ $proposal->id }})">
                                        <i class="bx bx-transfer me-1"></i>{{ __('Convert to Invoice') }}
                                    </a>
                                @endcan
                                @can('billing.proposals.duplicate')
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="duplicateProposal({{ $proposal->id }})">
                                        <i class="bx bx-copy me-1"></i>{{ __('Duplicate') }}
                                    </a>
                                @endcan
                                @can('billing.proposals.print')
                                    <a class="dropdown-item" href="{{ route('billing.proposals.print', $proposal->id) }}" target="_blank">
                                        <i class="bx bx-printer me-1"></i>{{ __('Print') }}
                                    </a>
                                @endcan
                                @can('billing.proposals.delete')
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="deleteProposal({{ $proposal->id }})">
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

@can('billing.proposals.create')
<!-- Create Proposal Modal -->
<div class="modal fade" id="createProposalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Create New Proposal') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form id="createProposalForm" action="{{ route('billing.proposals.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- Proposal form fields would go here -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="client_id" class="form-label">{{ __('Client') }}</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">{{ __('Select Client') }}</option>
                                <!-- Client options would be populated here -->
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="valid_until" class="form-label">{{ __('Valid Until') }}</label>
                            <input type="date" class="form-control" id="valid_until" name="valid_until" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Create Proposal') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

@endsection