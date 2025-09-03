@extends('layouts.layoutMaster')

@section('title', __('Product Details'))

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
        productsDelete: @json(route('wmsinventorycore.products.destroy', ['product' => '__PRODUCT_ID__'])),
        productsIndex: @json(route('wmsinventorycore.products.index'))
      },
      labels: {
        confirmDelete: @json(__('Are you sure?')),
        confirmDeleteText: @json(__("You won't be able to revert this!")),
        confirmDeleteButton: @json(__('Yes, delete it!')),
        deleted: @json(__('Deleted!')),
        deletedText: @json(__('Product has been deleted.')),
        error: @json(__('Error!')),
        couldNotDelete: @json(__('Could not delete product.'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-product-show.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Products'), 'url' => route('wmsinventorycore.products.index')]
  ];
@endphp

<x-breadcrumb
  :title="$product->name"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
  <!-- Product Details Card -->
  <div class="col-xl-8 col-lg-7 col-md-7">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Product Information') }}</h5>
        <div>
          <a href="{{ route('wmsinventorycore.products.edit', $product->id) }}" class="btn btn-primary btn-sm me-1">
            <i class="bx bx-edit-alt me-1"></i> {{ __('Edit') }}
          </a>
          <button class="btn btn-danger btn-sm delete-product" data-id="{{ $product->id }}">
            <i class="bx bx-trash me-1"></i> {{ __('Delete') }}
          </button>
        </div>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('SKU') }}</h6>
              <p>{{ $product->sku }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Barcode') }}</h6>
              <p>{{ $product->barcode ?: __('N/A') }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Category') }}</h6>
              <p>{{ $product->category ? $product->category->name : __('N/A') }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Unit') }}</h6>
              <p>{{ $product->unit ? $product->unit->name : __('N/A') }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Cost Price') }}</h6>
              <p>{{ \App\Helpers\FormattingHelper::formatCurrency($product->cost_price) }}</p>
            </div>
          </div>
          <div class="col-md-4">
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Selling Price') }}</h6>
              <p>{{ \App\Helpers\FormattingHelper::formatCurrency($product->selling_price) }}</p>
            </div>
            <div class="mb-3">
              <h6 class="fw-semibold">{{ __('Status') }}</h6>
              <p>
                @if($product->status === 'active')
                <span class="badge bg-success">{{ __('Active') }}</span>
                @else
                <span class="badge bg-danger">{{ __('Inactive') }}</span>
                @endif
              </p>
            </div>
          </div>
        </div>
        
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Description') }}</h6>
          <p>{{ $product->description ?: __('No description available') }}</p>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Product Image Card -->
  <div class="col-xl-4 col-lg-5 col-md-5">
    @if($product->hasProductImage())
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Product Image') }}</h5>
      </div>
      <div class="card-body text-center">
        <img src="{{ $product->getProductImageUrl() }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 300px; object-fit: cover;">
      </div>
    </div>
    @endif
    
    <!-- Inventory Summary Card -->
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">{{ __('Inventory Summary') }}</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Total Stock') }}</h6>
          <h4>{{ $totalStock }} {{ $product->unit ? $product->unit->name : '' }}</h4>
        </div>
        
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Low Stock Threshold') }}</h6>
          <p>{{ $product->alert_on ?? __('Not set') }} {{ $product->unit ? $product->unit->name : '' }}</p>
        </div>
        
        <div class="mb-3">
          <h6 class="fw-semibold">{{ __('Inventory Value') }}</h6>
          <p>{{ $totalValue }}</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Warehouse Stock Card -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Stock by Warehouse') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('Warehouse') }}</th>
            <th>{{ __('Available Quantity') }}</th>
            <th>{{ __('Reserved Quantity') }}</th>
            <th>{{ __('Value') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($warehouseStock as $stock)
          <tr>
            <td>{{ $stock->warehouse->name }}</td>
            <td>{{ $stock->available_quantity }} {{ $product->unit ? $product->unit->name : '' }}</td>
            <td>{{ $stock->reserved_quantity }} {{ $product->unit ? $product->unit->name : '' }}</td>
            <td>{{ $stock->value }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="text-center">{{ __('No stock information available') }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Recent Transactions Card -->
<div class="card mb-4">
  <div class="card-header">
    <h5 class="mb-0">{{ __('Recent Transactions') }}</h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Type') }}</th>
            <th>{{ __('Reference') }}</th>
            <th>{{ __('Warehouse') }}</th>
            <th>{{ __('Quantity') }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse($transactions as $transaction)
          <tr>
            <td>{{ \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d H:i') }}</td>
            <td>{{ ucfirst($transaction->transaction_type) }}</td>
            <td>{{ $transaction->reference_type }} #{{ $transaction->reference_id }}</td>
            <td>{{ $transaction->warehouse->name }}</td>
            <td>
              @if(in_array($transaction->transaction_type, ['inbound', 'purchase', 'return', 'void']))
              <span class="text-success">+{{ abs($transaction->quantity) }}</span>
              @else
              <span class="text-danger">-{{ abs($transaction->quantity) }}</span>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center">{{ __('No transactions found') }}</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
