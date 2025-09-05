@extends('layouts.layoutMaster')

@section('title', 'Products')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
    'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        productsData: @json(route('wmsinventorycore.products.data')),
        productsShow: @json(route('wmsinventorycore.products.show', ['product' => '__PRODUCT_ID__'])),
        productsEdit: @json(route('wmsinventorycore.products.edit', ['product' => '__PRODUCT_ID__'])),
        productsDelete: @json(route('wmsinventorycore.products.destroy', ['product' => '__PRODUCT_ID__'])),
        productsCreate: @json(route('wmsinventorycore.products.create'))
      }
    };
  </script>
  @vite(['resources/assets/js/app/wms-inventory-products.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')]
  ];
@endphp

<x-breadcrumb
  :title="__('Products')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">{{ __('All Products') }}</h5>
      @can('wmsinventory.create-product')
        <a href="{{ route('wmsinventorycore.products.create') }}" class="btn btn-primary">
          <i class="bx bx-plus"></i> {{ __('Add New Product') }}
        </a>
      @endcan
    </div>
  </div>
  <div class="card-body">
    <div class="row mb-4">
      <div class="col-md-4">
        <label class="form-label">{{ __('Filter by Category') }}</label>
        <select id="category-filter" class="select2 form-select" data-placeholder="{{ __('Select Category') }}">
          <option value="">{{ __('All Categories') }}</option>
          @foreach($categories as $category)
          <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">{{ __('Filter by Warehouse') }}</label>
        <select id="warehouse-filter" class="select2 form-select" data-placeholder="{{ __('Select Warehouse') }}">
          <option value="">{{ __('All Warehouses') }}</option>
          @foreach($warehouses as $warehouse)
          <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
          @endforeach
        </select>
      </div>
    </div>

    <div class="card-datatable table-responsive">
      <table id="products-table" class="table table-bordered">
        <thead>
          <tr>
            <th>{{ __('ID') }}</th>
            <th>{{ __('Image') }}</th>
            <th>{{ __('Name') }}</th>
            <th>{{ __('SKU') }}</th>
            <th>{{ __('Barcode') }}</th>
            <th>{{ __('Category') }}</th>
            <th>{{ __('Unit') }}</th>
            <th>{{ __('Stock') }}</th>
            <th>{{ __('Cost Price') }}</th>
            <th>{{ __('Selling Price') }}</th>
            <th>{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
