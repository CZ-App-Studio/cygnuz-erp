@extends('layouts.layoutMaster')

@section('title', __('Low Stock Report'))

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
  :title="__('Low Stock Report')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Low Stock Report') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('wmsinventorycore.reports.low-stock') }}" method="get">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
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
                        <div class="col-md-4 align-self-end">
                            <button type="submit" class="btn btn-primary">{{ __('Generate Report') }}</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('Warehouse') }}</th>
                                <th>{{ __('SKU') }}</th>
                                <th>{{ __('Product') }}</th>
                                <th class="text-end">{{ __('Current Stock') }}</th>
                                <th class="text-end">{{ __('Reorder Point') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Recommended Order') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($lowStockProducts->isEmpty())
                                <tr>
                                    <td colspan="7" class="text-center">{{ __('No low stock items found.') }}</td>
                                </tr>
                            @else
                                @foreach($lowStockProducts as $wp)
                                    <tr>
                                        <td>{{ $wp->warehouse->name }}</td>
                                        <td>{{ $wp->product->sku }}</td>
                                        <td>{{ $wp->product->name }}</td>
                                        <td class="text-end">{{ $wp->stock_level }} {{ $wp->product->unit->abbreviation ?? '' }}</td>
                                        <td class="text-end">{{ $wp->product->reorder_point }} {{ $wp->product->unit->abbreviation ?? '' }}</td>
                                        <td class="text-center">
                                            @if($wp->stock_level == 0)
                                                <span class="badge bg-danger">{{ __('Out of Stock') }}</span>
                                            @elseif($wp->stock_level <= ($wp->product->reorder_point * 0.5))
                                                <span class="badge bg-warning">{{ __('Critical') }}</span>
                                            @else
                                                <span class="badge bg-info">{{ __('Low') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            {{ max(0, $wp->product->reorder_point - $wp->stock_level + ($wp->product->reorder_quantity ?? $wp->product->reorder_point)) }}
                                            {{ $wp->product->unit->abbreviation ?? '' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
