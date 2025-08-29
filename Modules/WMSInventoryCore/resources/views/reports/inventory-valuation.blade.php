@extends('layouts.layoutMaster')

@section('title', __('Inventory Valuation Report'))

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
  :title="__('Inventory Valuation Report')"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Inventory Valuation Report') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('wmsinventorycore.reports.inventory-valuation') }}" method="get">
                    <div class="row g-3 mb-4">
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
                        <div class="col-md-3">
                            <label class="form-label" for="as_of_date">{{ __('As of Date') }}</label>
                            <input type="text" id="as_of_date" name="as_of_date" class="form-control flatpickr-date" placeholder="YYYY-MM-DD" value="{{ $asOfDate ?? date('Y-m-d') }}" />
                        </div>
                        <div class="col-md-3 align-self-end">
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
                                <th class="text-end">{{ __('Quantity') }}</th>
                                <th class="text-end">{{ __('Unit Cost') }}</th>
                                <th class="text-end">{{ __('Total Value') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($warehouseProducts as $wp)
                                <tr>
                                    <td>{{ $wp->warehouse->name }}</td>
                                    <td>{{ $wp->product->sku }}</td>
                                    <td>{{ $wp->product->name }}</td>
                                    <td class="text-end">{{ $wp->stock_level }} {{ $wp->product->unit->abbreviation ?? '' }}</td>
                                    <td class="text-end">{{ App\Helpers\FormattingHelper::formatCurrency($wp->product->cost_price) }}</td>
                                    <td class="text-end">{{ App\Helpers\FormattingHelper::formatCurrency($wp->stock_level * $wp->product->cost_price) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">{{ __('Total Inventory Value') }}:</th>
                                <th class="text-end">{{ $totalValue }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
