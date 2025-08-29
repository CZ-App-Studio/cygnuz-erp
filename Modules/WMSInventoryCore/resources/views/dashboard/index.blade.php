@extends('layouts.layoutMaster')

@section('title', __('WMS & Inventory Dashboard'))

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('page-script')
  <script>
    const pageData = {
      warehouseValues: @json($warehouseValues),
      monthlyTransactions: @json($monthlyTransactions),
      labels: {
        purchases: @json(__('Purchases')),
        sales: @json(__('Sales')),
        adjustments: @json(__('Adjustments'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-dashboard.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [];
@endphp

<x-breadcrumb
  :title="__('WMS & Inventory Dashboard')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <!-- Stat Cards -->
  <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text">{{ __('Total Products') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2">{{ $stats['total_products'] }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-primary rounded p-2">
              <i class="bx bx-box bx-md"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text">{{ __('Total Warehouses') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2">{{ $stats['total_warehouses'] }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-success rounded p-2">
              <i class="bx bx-store bx-md"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text">{{ __('Total Stock Value') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2">{{ App\Helpers\FormattingHelper::formatCurrency($stats['total_stock_value']) }}</h4>
            </div>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-info rounded p-2">
              <i class="bx bx-money bx-md"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <div class="card-info">
            <p class="card-text">{{ __('Pending Tasks') }}</p>
            <div class="d-flex align-items-end mb-2">
              <h4 class="card-title mb-0 me-2">{{ $stats['pending_adjustments'] + $stats['pending_transfers'] }}</h4>
            </div>
            <small>{{ $stats['pending_adjustments'] }} {{ __('Adjustments') }}, {{ $stats['pending_transfers'] }} {{ __('Transfers') }}</small>
          </div>
          <div class="card-icon">
            <span class="badge bg-label-warning rounded p-2">
              <i class="bx bx-task bx-md"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Inventory Value by Warehouse Chart -->
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0">{{ __('Inventory Value by Warehouse') }}</h5>
      </div>
      <div class="card-body">
        <div id="inventoryValueChart" class="px-2"></div>
      </div>
    </div>
  </div>
  
  <!-- Monthly Transaction Chart -->
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0">{{ __('Monthly Transactions') }}</h5>
      </div>
      <div class="card-body">
        <div id="transactionChart" class="px-2"></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Low Stock Products -->
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0">{{ __('Low Stock Products') }}</h5>
        @can('wmsinventory.view-low-stock')
          <a href="{{ route('wmsinventorycore.reports.low-stock') }}" class="btn btn-sm btn-primary">{{ __('View All') }}</a>
        @endcan
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Category') }}</th>
                <th>{{ __('Current Stock') }}</th>
                <th>{{ __('Min. Stock') }}</th>
                <th>{{ __('Status') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($lowStockProducts as $product)
              <tr>
                <td>{{ $product->name }}</td>
                <td>{{ $product->category ? $product->category->name : '-' }}</td>
                <td>{{ $product->inventories->sum('stock_level') }}</td>
                <td>{{ $product->min_stock_level }}</td>
                <td>
                  @if($product->inventories->sum('stock_level') == 0)
                  <span class="badge bg-danger">{{ __('Out of Stock') }}</span>
                  @else
                  <span class="badge bg-warning">{{ __('Low Stock') }}</span>
                  @endif
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center">{{ __('No low stock products found') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Recent Transactions -->
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0">{{ __('Recent Transactions') }}</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>{{ __('Date') }}</th>
                <th>{{ __('Product') }}</th>
                <th>{{ __('Warehouse') }}</th>
                <th>{{ __('Type') }}</th>
                <th>{{ __('Quantity') }}</th>
              </tr>
            </thead>
            <tbody>
              @forelse($recentTransactions as $transaction)
              <tr>
                <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y') }}</td>
                <td>{{ $transaction->product->name }}</td>
                <td>{{ $transaction->warehouse->name }}</td>
                <td>
                  @switch($transaction->transaction_type)
                    @case('purchase')
                      <span class="badge bg-success">{{ __('Purchase') }}</span>
                      @break
                    @case('sale')
                      <span class="badge bg-info">{{ __('Sale') }}</span>
                      @break
                    @case('adjustment')
                      <span class="badge bg-warning">{{ __('Adjustment') }}</span>
                      @break
                    @case('transfer_in')
                      <span class="badge bg-primary">{{ __('Transfer In') }}</span>
                      @break
                    @case('transfer_out')
                      <span class="badge bg-secondary">{{ __('Transfer Out') }}</span>
                      @break
                    @default
                      <span class="badge bg-dark">{{ ucfirst($transaction->transaction_type) }}</span>
                  @endswitch
                </td>
                <td>{{ $transaction->quantity }}</td>
              </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center">{{ __('No recent transactions found') }}</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
