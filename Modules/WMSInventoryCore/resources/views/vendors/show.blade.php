@extends('layouts.layoutMaster')

@section('title', __('Vendor Details'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    'resources/assets/vendor/libs/apex-charts/apex-charts.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/apex-charts/apexcharts.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        vendorsDelete: @json(route('wmsinventorycore.vendors.destroy', $vendor->id)),
        vendorsIndex: @json(route('wmsinventorycore.vendors.index'))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Vendor has been deleted.')),
        error: @json(__('Error!')),
        couldNotDelete: @json(__('Could not delete vendor.'))
      },
      monthlyPurchases: @json($monthlyPurchases)
    };

    $(function () {
      // CSRF setup
      $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
      });

      // Initialize purchases DataTable
      $('#purchases-table').DataTable({
        responsive: true,
        language: {
          paginate: {
            previous: '&nbsp;',
            next: '&nbsp;'
          }
        }
      });

      // Initialize monthly purchases chart
      initializePurchasesChart();
    });

    function deleteVendor() {
      Swal.fire({
        title: pageData.labels.confirmDelete,
        text: pageData.labels.confirmDeleteText,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: pageData.labels.confirmDeleteButton,
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
      }).then(function (result) {
        if (result.value) {
          $.ajax({
            url: pageData.urls.vendorsDelete,
            type: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
              if (response.status === 'success') {
                Swal.fire({
                  icon: 'success',
                  title: pageData.labels.deleted,
                  text: pageData.labels.deletedText,
                  customClass: {
                    confirmButton: 'btn btn-success'
                  }
                }).then(() => {
                  window.location.href = pageData.urls.vendorsIndex;
                });
              } else {
                Swal.fire({
                  icon: 'error',
                  title: pageData.labels.error,
                  text: response.data || pageData.labels.couldNotDelete,
                  customClass: {
                    confirmButton: 'btn btn-success'
                  }
                });
              }
            },
            error: function () {
              Swal.fire({
                icon: 'error',
                title: pageData.labels.error,
                text: pageData.labels.couldNotDelete,
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
            }
          });
        }
      });
    }

    function initializePurchasesChart() {
      if (pageData.monthlyPurchases.length === 0) return;

      const chartData = pageData.monthlyPurchases.map(item => ({
        x: new Date(item.year, item.month - 1),
        y: parseFloat(item.total) || 0
      })).reverse();

      const options = {
        chart: {
          type: 'area',
          height: 300,
          toolbar: { show: false }
        },
        series: [{
          name: @json(__('Purchase Amount')),
          data: chartData
        }],
        colors: ['#696cff'],
        fill: {
          type: 'gradient',
          gradient: {
            shadeIntensity: 1,
            opacityFrom: 0.7,
            opacityTo: 0.1
          }
        },
        stroke: {
          curve: 'smooth',
          width: 2
        },
        xaxis: {
          type: 'datetime',
          labels: {
            format: 'MMM yyyy'
          }
        },
        yaxis: {
          labels: {
            formatter: function (val) {
              return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
              }).format(val);
            }
          }
        },
        tooltip: {
          x: {
            format: 'MMM yyyy'
          },
          y: {
            formatter: function (val) {
              return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
              }).format(val);
            }
          }
        }
      };

      const chart = new ApexCharts(document.querySelector("#purchasesChart"), options);
      chart.render();
    }
  </script>
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Vendors'), 'url' => route('wmsinventorycore.vendors.index')]
  ];
@endphp

<x-breadcrumb
  :title="$vendor->name"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <!-- Vendor Details Card -->
  <div class="col-xl-8 col-lg-7 col-md-7">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Vendor Information') }}</h5>
        <div>
          <a href="{{ route('wmsinventorycore.vendors.edit', $vendor->id) }}" class="btn btn-primary btn-sm me-1">
            <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
          </a>
          <button class="btn btn-danger btn-sm" onclick="deleteVendor()">
            <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Company Name') }}</h6>
              <p>{{ $vendor->company_name ?: __('N/A') }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Email') }}</h6>
              <p>{{ $vendor->email }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Phone') }}</h6>
              <p>{{ $vendor->phone_number ?: __('N/A') }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Website') }}</h6>
              @if($vendor->website)
                <a href="{{ $vendor->website }}" target="_blank">{{ $vendor->website }}</a>
              @else
                <p>{{ __('N/A') }}</p>
              @endif
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Tax Number') }}</h6>
              <p>{{ $vendor->tax_number ?: __('N/A') }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Status') }}</h6>
              <p>
                @if($vendor->status === 'active')
                <span class="badge bg-success">{{ __('Active') }}</span>
                @else
                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                @endif
              </p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Address') }}</h6>
              <p>
                @if($vendor->address)
                  {{ $vendor->address }}<br>
                  @if($vendor->city){{ $vendor->city }}, @endif
                  @if($vendor->state){{ $vendor->state }} @endif
                  @if($vendor->postal_code){{ $vendor->postal_code }}<br>@endif
                  @if($vendor->country){{ $vendor->country }}@endif
                @else
                  {{ __('N/A') }}
                @endif
              </p>
            </div>
          </div>
        </div>

        <!-- Purchase Terms -->
        <div class="row mb-3">
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Payment Terms') }}</h6>
              <p>{{ $vendor->payment_terms ?: __('N/A') }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Lead Time') }}</h6>
              <p>{{ $vendor->lead_time_days ? $vendor->lead_time_days . ' ' . __('days') : __('N/A') }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Minimum Order Value') }}</h6>
              <p>{{ $vendor->minimum_order_value ? \App\Helpers\FormattingHelper::formatCurrency($vendor->minimum_order_value) : __('N/A') }}</p>
            </div>
          </div>
        </div>
        
        @if($vendor->notes)
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Notes') }}</h6>
          <p>{{ $vendor->notes }}</p>
        </div>
        @endif
      </div>
    </div>
  </div>
  
  <!-- Statistics Cards -->
  <div class="col-xl-4 col-lg-5 col-md-5">
    <!-- Purchase Statistics -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Purchase Statistics') }}</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Total Purchases') }}</h6>
          <h4 class="text-primary">{{ $purchaseCount }}</h4>
        </div>
        
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Total Amount') }}</h6>
          <h4 class="text-success">{{ $totalPurchases }}</h4>
        </div>
        
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Outstanding Balance') }}</h6>
          <h4 class="text-warning">{{ $outstandingBalance }}</h4>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Quick Actions') }}</h5>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="{{ route('wmsinventorycore.vendors.edit', $vendor->id) }}" class="btn btn-primary">
            <i class="bx bx-edit-alt me-2"></i>{{ __('Edit Vendor') }}
          </a>
          <a href="{{ route('wmsinventorycore.vendors.index') }}" class="btn btn-label-secondary">
            <i class="bx bx-arrow-back me-2"></i>{{ __('Back to Vendors') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Monthly Purchases Chart -->
@if($monthlyPurchases->isNotEmpty())
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Monthly Purchase Trend') }}</h5>
  </div>
  <div class="card-body">
    <div id="purchasesChart"></div>
  </div>
</div>
@endif

<!-- Recent Purchases -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Recent Purchases') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="purchases-table" class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Warehouse') }}</th>
            <th>{{ __('Total Amount') }}</th>
            <th>{{ __('Paid Amount') }}</th>
            <th>{{ __('Status') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentPurchases as $purchase)
          <tr>
            <td>{{ $purchase->id }}</td>
            <td>{{ \App\Helpers\FormattingHelper::formatDate($purchase->created_at) }}</td>
            <td>{{ $purchase->reference_number ?: '-' }}</td>
            <td>{{ $purchase->warehouse ? $purchase->warehouse->name : __('N/A') }}</td>
            <td>{{ \App\Helpers\FormattingHelper::formatCurrency($purchase->total_amount) }}</td>
            <td>{{ \App\Helpers\FormattingHelper::formatCurrency($purchase->paid_amount) }}</td>
            <td>
              @switch($purchase->status ?? 'pending')
                @case('completed')
                  <span class="badge bg-success">{{ __('Completed') }}</span>
                  @break
                @case('pending')
                  <span class="badge bg-warning">{{ __('Pending') }}</span>
                  @break
                @case('cancelled')
                  <span class="badge bg-danger">{{ __('Cancelled') }}</span>
                  @break
                @default
                  <span class="badge bg-secondary">{{ __('Unknown') }}</span>
              @endswitch
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center">{{ __('No purchases found for this vendor') }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection