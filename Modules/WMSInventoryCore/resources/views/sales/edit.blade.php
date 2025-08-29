@extends('layouts.layoutMaster')

@section('title', __('Edit Sales Order'))

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/select2/select2.scss',
    'resources/assets/vendor/libs/flatpickr/flatpickr.scss',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/select2/select2.js',
    'resources/assets/vendor/libs/flatpickr/flatpickr.js',
    'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    'resources/assets/vendor/libs/jquery-repeater/jquery-repeater.js'
  ])
@endsection

@section('page-script')
  <script>
    const pageData = {
      urls: {
        productSearch: @json(route('wmsinventorycore.products.search')),
        customerSearch: @json(route('wmsinventorycore.customers.search')),
        warehouseSearch: @json(route('wmsinventorycore.warehouses.search')),
        update: @json(route('wmsinventorycore.sales.update', $sale->id)),
        show: @json(route('wmsinventorycore.sales.show', $sale->id)),
        index: @json(route('wmsinventorycore.sales.index'))
      },
      labels: {
        selectProduct: @json(__('Select Product')),
        selectCustomer: @json(__('Select Customer')),
        selectWarehouse: @json(__('Select Warehouse')),
        success: @json(__('Success!')),
        salesOrderUpdated: @json(__('Sales order updated successfully.')),
        error: @json(__('Error!')),
        validationError: @json(__('Please check the form for errors.')),
        limitedEditing: @json(__('Limited Editing')),
        limitedEditingText: @json(__('This sales order can only be partially edited because it has been approved or is being processed.'))
      },
      sale: @json($sale->load(['customer', 'warehouse', 'products.product'])),
      isEditable: @json($sale->status === 'draft')
    };
  </script>
  @vite(['Modules/WMSInventoryCore/resources/assets/js/wms-inventory-sale-edit.js'])
@endsection

@section('content')
@php
  $breadcrumbs = [
    ['name' => __('WMS & Inventory'), 'url' => route('wmsinventorycore.dashboard.index')],
    ['name' => __('Sales Orders'), 'url' => route('wmsinventorycore.sales.index')],
    ['name' => $sale->code, 'url' => route('wmsinventorycore.sales.show', $sale->id)]
  ];
@endphp

<x-breadcrumb
  :title="__('Edit Sales Order') . ' - ' . $sale->code"
  :breadcrumbs="$breadcrumbs"
  :homeUrl="route('dashboard')"
/>

@if($sale->status !== 'draft')
<div class="alert alert-warning" role="alert">
  <h6 class="alert-heading">{{ __('Limited Editing') }}</h6>
  {{ __('This sales order has been approved and most fields are now read-only. You can only edit notes and some administrative fields.') }}
</div>
@endif

<div class="row">
  <div class="col-12">
    <form id="salesForm">
      @csrf
      @method('PUT')

      <!-- Sales Order Header -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Sales Order Information') }}</h5>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-3 mb-3">
              <label class="form-label" for="so_number">{{ __('SO Number') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="so_number" name="so_number" value="{{ $sale->code }}" readonly />
            </div>
            
            <div class="col-md-3 mb-3">
              <label class="form-label" for="sale_date">{{ __('Sale Date') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control datepicker" id="sale_date" name="sale_date" value="{{ $sale->date ? $sale->date->format('Y-m-d') : '' }}" {{ $sale->status !== 'draft' ? 'readonly' : 'required' }} />
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="expected_delivery_date">{{ __('Expected Delivery Date') }}</label>
              <input type="text" class="form-control datepicker" id="expected_delivery_date" name="expected_delivery_date" value="{{ $sale->expected_delivery_date?->format('Y-m-d') }}" {{ $sale->status !== 'draft' ? 'readonly' : '' }} />
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label" for="status">{{ __('Status') }} <span class="text-danger">*</span></label>
              <select id="status" name="status" class="form-select" {{ $sale->status !== 'draft' ? 'disabled' : 'required' }}>
                <option value="draft" @selected($sale->status === 'draft')>{{ __('Draft') }}</option>
                <option value="pending" @selected($sale->status === 'pending')>{{ __('Pending Approval') }}</option>
                <option value="approved" @selected($sale->status === 'approved')>{{ __('Approved') }}</option>
                <option value="cancelled" @selected($sale->status === 'cancelled')>{{ __('Cancelled') }}</option>
                <option value="rejected" @selected($sale->status === 'rejected')>{{ __('Rejected') }}</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="customer_id">{{ __('Customer') }} <span class="text-danger">*</span></label>
              <select id="customer_id" name="customer_id" class="form-select" {{ $sale->status !== 'draft' ? 'disabled' : 'required' }}>
                <option value="{{ $sale->customer_id }}" selected>{{ $sale->customer->name }} @if($sale->customer->company_name)({{ $sale->customer->company_name }})@endif</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="warehouse_id">{{ __('Warehouse') }} <span class="text-danger">*</span></label>
              <select id="warehouse_id" name="warehouse_id" class="form-select" {{ $sale->status !== 'draft' ? 'disabled' : 'required' }}>
                <option value="{{ $sale->warehouse_id }}" selected>{{ $sale->warehouse->name }}</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label" for="payment_terms">{{ __('Payment Terms') }}</label>
              <input type="text" class="form-control" id="payment_terms" name="payment_terms" value="{{ $sale->payment_terms }}" placeholder="{{ __('e.g., Net 30 days') }}" />
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label" for="reference">{{ __('Reference') }}</label>
              <input type="text" class="form-control" id="reference" name="reference" value="{{ $sale->reference }}" placeholder="{{ __('Enter reference number') }}" />
            </div>
          </div>
        </div>
      </div>

      <!-- Sales Order Items -->
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">{{ __('Order Items') }}</h5>
        </div>
        <div class="card-body">
          <div id="product-repeater">
            <div data-repeater-list="items">
              @forelse($sale->products ?? [] as $item)
              <div data-repeater-item class="repeater-item">
                <input type="hidden" name="id" value="{{ $item->id }}" />
                <div class="row align-items-end">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Product') }} <span class="text-danger">*</span></label>
                    @if($sale->status === 'draft')
                      <select name="product_id" class="form-select product-select" required>
                        <option value="{{ $item->product_id }}" selected>{{ $item->product->name }} ({{ $item->product->sku }})</option>
                      </select>
                    @else
                      <input type="text" class="form-control" value="{{ $item->product->name }} ({{ $item->product->sku }})" readonly />
                      <input type="hidden" name="product_id" value="{{ $item->product_id }}" />
                    @endif
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="form-label">{{ __('Quantity') }} <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control quantity" min="0" step="0.01" value="{{ $item->quantity }}" {{ $sale->status !== 'draft' ? 'readonly' : 'required' }} />
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="form-label">{{ __('Unit Price') }} <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control unit-price" min="0" step="0.01" value="{{ $item->unit_price }}" {{ $sale->status !== 'draft' ? 'readonly' : 'required' }} />
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="form-label">{{ __('Total') }}</label>
                    <input type="number" name="line_total" class="form-control line-total" value="{{ $item->subtotal }}" readonly />
                  </div>

                  <div class="col-md-2 mb-3">
                    @if($sale->status === 'draft')
                      <button type="button" class="btn btn-label-danger" data-repeater-delete>
                        <i class="bx bx-trash"></i>
                      </button>
                    @endif
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 mb-3">
                    <label class="form-label">{{ __('Notes') }}</label>
                    <input type="text" name="notes" class="form-control" value="{{ $item->notes }}" placeholder="{{ __('Item notes') }}" {{ $sale->status !== 'draft' ? 'readonly' : '' }} />
                  </div>
                </div>
                <hr>
              </div>
              @empty
              <div data-repeater-item class="repeater-item">
                <div class="row align-items-end">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">{{ __('Product') }} <span class="text-danger">*</span></label>
                    <select name="product_id" class="form-select product-select" required>
                    </select>
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="form-label">{{ __('Quantity') }} <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control quantity" min="0" step="0.01" required />
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="form-label">{{ __('Unit Price') }} <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control unit-price" min="0" step="0.01" required />
                  </div>

                  <div class="col-md-2 mb-3">
                    <label class="form-label">{{ __('Total') }}</label>
                    <input type="number" name="line_total" class="form-control line-total" readonly />
                  </div>

                  <div class="col-md-2 mb-3">
                    <button type="button" class="btn btn-label-danger" data-repeater-delete>
                      <i class="bx bx-trash"></i>
                    </button>
                  </div>
                </div>

                <div class="row">
                  <div class="col-12 mb-3">
                    <label class="form-label">{{ __('Notes') }}</label>
                    <input type="text" name="notes" class="form-control" placeholder="{{ __('Item notes') }}" />
                  </div>
                </div>
                <hr>
              </div>
              @endforelse
            </div>
            @if($sale->status === 'draft')
            <div class="mb-3">
              <button type="button" class="btn btn-primary" data-repeater-create>
                <i class="bx bx-plus"></i> {{ __('Add Item') }}
              </button>
            </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Totals and Additional Information -->
      <div class="row">
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">{{ __('Additional Information') }}</h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label" for="notes">{{ __('Sales Order Notes') }}</label>
                <textarea class="form-control" id="notes" name="notes" rows="4" placeholder="{{ __('Enter any notes for this sales order') }}">{{ $sale->notes }}</textarea>
              </div>

              <div class="mb-3">
                <label class="form-label" for="terms_conditions">{{ __('Terms & Conditions') }}</label>
                <textarea class="form-control" id="terms_conditions" name="terms_conditions" rows="3" placeholder="{{ __('Enter terms and conditions') }}">{{ $sale->terms_conditions }}</textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <h5 class="card-title mb-0">{{ __('Order Summary') }}</h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <span>{{ __('Subtotal') }}:</span>
                <input type="number" id="subtotal" name="subtotal" class="form-control form-control-sm w-auto text-end" value="{{ $sale->subtotal }}" readonly />
              </div>

              <div class="d-flex justify-content-between mb-2">
                <label class="form-label mb-0">{{ __('Discount') }} (%):</label>
                <input type="number" id="discount_percentage" name="discount_percentage" class="form-control form-control-sm w-25" min="0" max="100" step="0.01" value="{{ $sale->discount_amount > 0 && $sale->subtotal > 0 ? round(($sale->discount_amount / $sale->subtotal) * 100, 2) : 0 }}" {{ $sale->status !== 'draft' ? 'readonly' : '' }} />
              </div>

              <div class="d-flex justify-content-between mb-2">
                <span>{{ __('Discount Amount') }}:</span>
                <input type="number" id="discount_amount" name="discount_amount" class="form-control form-control-sm w-auto text-end" value="{{ $sale->discount_amount }}" readonly />
              </div>

              <div class="d-flex justify-content-between mb-2">
                <label class="form-label mb-0">{{ __('Tax') }} (%):</label>
                <input type="number" id="tax_percentage" name="tax_percentage" class="form-control form-control-sm w-25" min="0" max="100" step="0.01" value="{{ $sale->tax_amount > 0 && $sale->subtotal > 0 ? round(($sale->tax_amount / ($sale->subtotal - $sale->discount_amount)) * 100, 2) : 0 }}" {{ $sale->status !== 'draft' ? 'readonly' : '' }} />
              </div>

              <div class="d-flex justify-content-between mb-2">
                <span>{{ __('Tax Amount') }}:</span>
                <input type="number" id="tax_amount" name="tax_amount" class="form-control form-control-sm w-auto text-end" value="{{ $sale->tax_amount }}" readonly />
              </div>

              <div class="d-flex justify-content-between mb-2">
                <label class="form-label mb-0">{{ __('Shipping Cost') }}:</label>
                <input type="number" id="shipping_cost" name="shipping_cost" class="form-control form-control-sm w-50" min="0" step="0.01" value="{{ $sale->shipping_cost }}" {{ $sale->status !== 'draft' ? 'readonly' : '' }} />
              </div>

              <hr>

              <div class="d-flex justify-content-between">
                <strong>{{ __('Total Amount') }}:</strong>
                <input type="number" id="total_amount" name="total_amount" class="form-control form-control-sm w-auto text-end fw-bold" value="{{ $sale->total_amount }}" readonly />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Form Actions -->
      <div class="row mt-4">
        <div class="col-12">
          <div class="card">
            <div class="card-body">
              <div class="d-flex gap-3">
                <button type="submit" class="btn btn-primary">{{ __('Update Sales Order') }}</button>
                <a href="{{ route('wmsinventorycore.sales.show', $sale->id) }}" class="btn btn-label-secondary">{{ __('Cancel') }}</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection