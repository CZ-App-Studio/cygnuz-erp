@extends('layouts.layoutMaster')

@section('title', __('Adjustment Details'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        adjustmentsIndex: @json(route('wmsinventorycore.adjustments.index')),
        adjustmentApprove: @json(route('wmsinventorycore.adjustments.approve', ['adjustment' => '__ADJUSTMENT_ID__'])),
        adjustmentDelete: @json(route('wmsinventorycore.adjustments.destroy', ['adjustment' => '__ADJUSTMENT_ID__']))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-adjustment-show.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Adjustments'), 'url' => route('wmsinventorycore.adjustments.index')]
  ];
@endphp

<x-breadcrumb
  :title="'#' . ($adjustment->code ?: $adjustment->id)"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('wmsinventorycore.dashboard.index')"
/>

<div class="row">
  <!-- Adjustment Details Card -->
  <div class="col-xl-8 col-lg-7">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Adjustment Information') }}</h5>
        <div>
          @if($adjustment->status === 'pending')
            <a href="{{ route('wmsinventorycore.adjustments.edit', $adjustment->id) }}" class="btn btn-primary btn-sm me-1">
              <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
            </a>
            <button type="button" class="btn btn-success btn-sm me-1" id="approve-adjustment" data-id="{{ $adjustment->id }}">
              <i class="bx bx-check-circle me-1"></i> {{ __('Approve') }}
            </button>
          @endif
          <button type="button" class="btn btn-info btn-sm me-1" id="print-adjustment" data-id="{{ $adjustment->id }}">
            <i class="bx bx-printer me-1"></i> {{ __('Print') }}
          </button>
          @if($adjustment->status === 'pending')
            <button type="button" class="btn btn-danger btn-sm delete-adjustment" data-id="{{ $adjustment->id }}">
              <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
            </button>
          @endif
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Adjustment ID') }}</h6>
              <p>#{{ $adjustment->code ?: $adjustment->id }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Date') }}</h6>
              <p>{{ \App\Helpers\FormattingHelper::formatDate($adjustment->date) }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Warehouse') }}</h6>
              <p>{{ $adjustment->warehouse->name }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Reference No.') }}</h6>
              <p>{{ $adjustment->reference_no ?: 'N/A' }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Adjustment Type') }}</h6>
              <p>
                {{ $adjustment->adjustmentType->name }}
                <span class="badge {{ $adjustment->adjustmentType->effect == 'increase' ? 'bg-success' : 'bg-danger' }}">
                  {{ ucfirst($adjustment->adjustmentType->effect) }}
                </span>
              </p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Status') }}</h6>
              <p>
                @if($adjustment->status === 'approved')
                <span class="badge bg-success">{{ __('Approved') }}</span>
                @elseif($adjustment->status === 'pending')
                <span class="badge bg-warning">{{ __('Pending') }}</span>
                @else
                <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                @endif
              </p>
            </div>
          </div>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-12">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Reason') }}</h6>
              <p>{{ $adjustment->reason }}</p>
            </div>
            @if($adjustment->notes)
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Notes') }}</h6>
              <p>{{ $adjustment->notes }}</p>
            </div>
            @endif
          </div>
        </div>
        
        @if($adjustment->status === 'approved')
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Approved By') }}</h6>
          <p>{{ $adjustment->approvedBy->name ?? 'N/A' }} 
            @if($adjustment->approved_at)
              {{ __('on') }} {{ \App\Helpers\FormattingHelper::formatDateTime($adjustment->approved_at) }}
            @endif
          </p>
        </div>
        @endif
      </div>
    </div>
  </div>
  
  <!-- Adjustment Summary Card -->
  <div class="col-xl-4 col-lg-5">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Summary') }}</h5>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Created By') }}</h6>
          </div>
          <p class="mb-0">{{ $adjustment->createdBy->name ?? 'N/A' }}</p>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Created At') }}</h6>
          </div>
          <p class="mb-0">{{ \App\Helpers\FormattingHelper::formatDateTime($adjustment->created_at) }}</p>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Last Updated') }}</h6>
          </div>
          <p class="mb-0">{{ \App\Helpers\FormattingHelper::formatDateTime($adjustment->updated_at) }}</p>
        </div>
        
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">{{ __('Total Items') }}</h6>
          </div>
          <p class="mb-0">{{ $adjustment->products->count() }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Products Card -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Products') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>{{ __('Product') }}</th>
            <th>{{ __('SKU') }}</th>
            <th>{{ __('Quantity') }}</th>
            <th>{{ __('Reason') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($adjustment->products as $index => $item)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->product->sku }}</td>
            <td>
              <span class="{{ $adjustment->adjustmentType->effect == 'increase' ? 'text-success' : 'text-danger' }}">
                {{ $adjustment->adjustmentType->effect == 'increase' ? '+' : '-' }}{{ $item->quantity }}
              </span>
            </td>
            <td>{{ $item->reason ?: 'N/A' }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center">{{ __('No products found') }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Audit Trail Card -->
@if($adjustment->audits->count() > 0)
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Audit Trail') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('Date & Time') }}</th>
            <th>{{ __('User') }}</th>
            <th>{{ __('Action') }}</th>
            <th>{{ __('Changes') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach($adjustment->audits as $audit)
          <tr>
            <td>{{ \App\Helpers\FormattingHelper::formatDateTime($audit->created_at) }}</td>
            <td>{{ $audit->user ? $audit->user->name : __('System') }}</td>
            <td>{{ ucfirst($audit->event) }}</td>
            <td>
              @if($audit->event != 'created')
                <ul class="mb-0">
                  @foreach($audit->getModified() as $attribute => $change)
                    @if($attribute != 'updated_at')
                      <li>
                        <strong>{{ ucfirst($attribute) }}:</strong> 
                        {{ $change['old'] ?? 'N/A' }} → {{ $change['new'] ?? 'N/A' }}
                      </li>
                    @endif
                  @endforeach
                </ul>
              @else
                <span class="text-muted">{{ __('Initial creation') }}</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif

<!-- Approve Adjustment Confirmation Modal -->
<div class="modal fade" id="approveAdjustmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('Approve Adjustment') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>{{ __('Are you sure you want to approve this adjustment? This action will update the inventory levels and cannot be undone.') }}</p>
        <form id="approveAdjustmentForm" action="{{ route('wmsinventorycore.adjustments.approve', $adjustment->id) }}" method="POST">
          @csrf
          @method('PATCH')
          <div class="mb-3">
            <label class="form-label" for="approval_notes">{{ __('Approval Notes (Optional)') }}</label>
            <textarea class="form-control" id="approval_notes" name="approval_notes" rows="3"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button type="button" class="btn btn-success" id="confirm-approve">{{ __('Approve Adjustment') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection
