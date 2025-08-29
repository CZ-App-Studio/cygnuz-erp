@extends('layouts.layoutMaster')

@section('title', __('Stock Movement Report'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/select2/select2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/select2/select2.js'
  ])
@endsection

@section('page-script')
  @vite(['resources/assets/js/app/app-inventory-reports.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Reports'), 'url' => '#']
  ];
@endphp

<x-breadcrumb
  :title="__('Stock Movement Report')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Stock Movement Report') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('wmsinventorycore.reports.stock-movement') }}" method="get">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label" for="product_id">{{ __('Product') }}</label>
                            <select id="product_id" name="product_id" class="form-select select2" required>
                                <option value="">{{ __('Select Product') }}</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ $selectedProduct == $product->id ? 'selected' : '' }}>
                                        {{ $product->name }} ({{ $product->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="warehouse_id">{{ __('Warehouse') }}</label>
                            <select id="warehouse_id" name="warehouse_id" class="form-select select2">
                                <option value="">{{ __('All Warehouses') }}</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ $selectedWarehouse == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="start_date">{{ __('Start Date') }}</label>
                            <input type="text" id="start_date" name="start_date" class="form-control flatpickr-date" placeholder="YYYY-MM-DD" value="{{ $startDate }}" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="end_date">{{ __('End Date') }}</label>
                            <input type="text" id="end_date" name="end_date" class="form-control flatpickr-date" placeholder="YYYY-MM-DD" value="{{ $endDate }}" />
                        </div>
                        <div class="col-md-2 align-self-end">
                            <button type="submit" class="btn btn-primary">{{ __('Generate Report') }}</button>
                        </div>
                    </div>
                </form>

                @if($selectedProduct)
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('Transaction Type') }}</th>
                                <th class="text-end">{{ __('Stock Before') }}</th>
                                <th class="text-end">{{ __('Quantity Change') }}</th>
                                <th class="text-end">{{ __('Stock After') }}</th>
                                <th>{{ __('Notes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $movement)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($movement->created_at)->format('Y-m-d H:i') }}</td>
                                    <td>
                                        {{ ucfirst($movement->transaction_type) }} #{{ $movement->reference_id }}
                                    </td>
                                    <td>{{ $movement->warehouse_name }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $movement->transaction_type)) }}</td>
                                    <td class="text-end">{{ $movement->stock_before }}</td>
                                    <td class="text-end {{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                    </td>
                                    <td class="text-end">{{ $movement->stock_after }}</td>
                                    <td>{{ $movement->notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                            
                            @if($movements->isEmpty())
                                <tr>
                                    <td colspan="8" class="text-center">{{ __('No movement records found for the selected criteria.') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                @else
                <div class="alert alert-info mt-3">
                    <div class="alert-body">
                        {{ __('Please select a product to view stock movement history.') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
