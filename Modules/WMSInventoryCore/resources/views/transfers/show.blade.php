@extends('layouts.layoutMaster')

@section('title', __('Transfer Details'))

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        transfersIndex: @json(route('wmsinventorycore.transfers.index')),
        transfersShip: @json(route('wmsinventorycore.transfers.ship', ['transfer' => $transfer->id])),
        transfersReceive: @json(route('wmsinventorycore.transfers.receive', ['transfer' => $transfer->id])),
        transfersCancel: @json(route('wmsinventorycore.transfers.cancel', ['transfer' => $transfer->id])),
        transfersDelete: @json(route('wmsinventorycore.transfers.destroy', ['transfer' => $transfer->id]))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-transfers.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Transfers'), 'url' => route('wmsinventorycore.transfers.index')]
  ];
@endphp

<x-breadcrumb
  :title="'#' . $transfer->id"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('wmsinventorycore.dashboard.index')"
/>

<div class="row">
  <!-- Transfer Details Card -->
  <div class="col-xl-8 col-lg-7">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Transfer Information') }}</h5>
        <div>
          @if($transfer->status == 'draft')
            <a href="{{ route('wmsinventorycore.transfers.edit', $transfer->id) }}" class="btn btn-primary btn-sm me-1">
              <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
            </a>
            <form method="POST" action="{{ route('wmsinventorycore.transfers.approve', $transfer->id) }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-success btn-sm me-1" onclick="return confirm('{{ __('Are you sure you want to approve this transfer?') }}')">
                <i class="bx bx-check me-1"></i> {{ __('Approve') }}
              </button>
            </form>
          @elseif($transfer->status == 'approved')
            <button type="button" class="btn btn-primary btn-sm me-1 ship-record" data-id="{{ $transfer->id }}">
              <i class="bx bx-send me-1"></i> {{ __('Ship') }}
            </button>
            <button type="button" class="btn btn-danger btn-sm me-1 cancel-record" data-id="{{ $transfer->id }}">
              <i class="bx bx-x me-1"></i> {{ __('Cancel') }}
            </button>
          @elseif($transfer->status == 'in_transit')
            <button type="button" class="btn btn-success btn-sm me-1 receive-record" data-id="{{ $transfer->id }}">
              <i class="bx bx-check-circle me-1"></i> {{ __('Receive') }}
            </button>
            <button type="button" class="btn btn-danger btn-sm me-1 cancel-record" data-id="{{ $transfer->id }}">
              <i class="bx bx-x me-1"></i> {{ __('Cancel') }}
            </button>
          @endif
          <button type="button" class="btn btn-info btn-sm me-1" id="print-transfer" data-id="{{ $transfer->id }}">
            <i class="bx bx-printer me-1"></i> {{ __('Print') }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Transfer ID') }}</h6>
              <p>#{{ $transfer->id }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Date') }}</h6>
              <p>{{ $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : __('N/A') }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Reference No.') }}</h6>
              <p>{{ $transfer->reference_no ?: __('N/A') }}</p>
            </div>
          </div>
          <div class="col-md-6">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Source Warehouse') }}</h6>
              <p>{{ $transfer->sourceWarehouse->name }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Destination Warehouse') }}</h6>
              <p>{{ $transfer->destinationWarehouse->name }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Status') }}</h6>
              <p>
                <span class="badge bg-{{ $transfer->status == 'draft' ? 'secondary' : ($transfer->status == 'approved' ? 'info' : ($transfer->status == 'in_transit' ? 'warning' : ($transfer->status == 'completed' ? 'success' : 'danger'))) }}">
                  {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                </span>
              </p>
            </div>
          </div>
        </div>
        
        <div class="row mb-3">
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Expected Arrival') }}</h6>
              <p>{{ $transfer->expected_arrival_date ? $transfer->expected_arrival_date->format('Y-m-d') : __('Not specified') }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Ship Date') }}</h6>
              <p>{{ $transfer->shipped_at ? $transfer->shipped_at->format('Y-m-d') : __('Not shipped yet') }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Arrival Date') }}</h6>
              <p>{{ $transfer->received_at ? $transfer->received_at->format('Y-m-d') : __('Not received yet') }}</p>
            </div>
          </div>
        </div>
        
        @if($transfer->shipping_cost > 0)
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Shipping Cost') }}</h6>
          <p>{{ $transfer->shipping_cost }}</p>
        </div>
        @endif
        
        @if($transfer->notes)
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Notes') }}</h6>
          <p>{{ $transfer->notes }}</p>
        </div>
        @endif
        
        @if($transfer->shipping_notes)
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Shipping Notes') }}</h6>
          <p>{{ $transfer->shipping_notes }}</p>
        </div>
        @endif
        
        @if($transfer->receiving_notes)
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Receiving Notes') }}</h6>
          <p>{{ $transfer->receiving_notes }}</p>
        </div>
        @endif
        
        @if($transfer->cancellation_reason)
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Cancellation Reason') }}</h6>
          <p>{{ $transfer->cancellation_reason }}</p>
        </div>
        @endif
      </div>
    </div>
  </div>
  
  <!-- Transfer Summary Card -->
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
          <p class="mb-0">{{ $transfer->createdBy->name ?? __('N/A') }}</p>
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Created At') }}</h6>
          </div>
          <p class="mb-0">{{ $transfer->created_at->format('Y-m-d H:i') }}</p>
        </div>
        
        @if($transfer->status != 'draft')
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Approved By') }}</h6>
          </div>
          <p class="mb-0">{{ $transfer->approvedBy->name ?? __('N/A') }}</p>
        </div>
        @endif
        
        @if($transfer->status == 'in_transit' || $transfer->status == 'completed')
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Shipped By') }}</h6>
          </div>
          <p class="mb-0">{{ $transfer->shippedBy->name ?? __('N/A') }}</p>
        </div>
        @endif
        
        @if($transfer->status == 'completed')
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="mb-0">{{ __('Received By') }}</h6>
          </div>
          <p class="mb-0">{{ $transfer->receivedBy->name ?? __('N/A') }}</p>
        </div>
        @endif
        
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-0">{{ __('Total Items') }}</h6>
          </div>
          <p class="mb-0">{{ $transfer->products->count() }}</p>
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
            <th>{{ __('Notes') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transfer->products as $index => $item)
          <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->product->sku }}</td>
            <td>{{ $item->quantity }} {{ $item->product->unit->code ?? '' }}</td>
            <td>{{ $item->notes ?: __('N/A') }}</td>
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
@if($transfer->audits->count() > 0)
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
          @foreach($transfer->audits as $audit)
          <tr>
            <td>{{ $audit->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ $audit->user ? $audit->user->name : __('System') }}</td>
            <td>{{ ucfirst($audit->event) }}</td>
            <td>
              @if($audit->event != 'created')
                <ul class="mb-0">
                  @foreach($audit->getModified() as $attribute => $change)
                    @if($attribute != 'updated_at')
                      <li>
                        <strong>{{ ucfirst($attribute) }}:</strong> 
                        {{ $change['old'] ?? __('N/A') }} → {{ $change['new'] ?? __('N/A') }}
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
@endsection
