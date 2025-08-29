<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Sales Order') }} #{{ $sale->code }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
        .info-section {
            margin-bottom: 30px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-block {
            width: 48%;
        }
        .info-label {
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            border: 1px solid #ddd;
            padding: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-section {
            margin-top: 30px;
            float: right;
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }
        .total-label {
            font-weight: bold;
        }
        .grand-total {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        .notes-section {
            clear: both;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-draft { background-color: #e2e3e5; color: #383d41; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-fulfilled { background-color: #d1ecf1; color: #0c5460; }
        .status-shipped { background-color: #cce5f0; color: #084c61; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #343a40; color: #fff; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ config('app.name', 'Company Name') }}</div>
            <div>{{ config('app.address', '') }}</div>
            <div>{{ config('app.phone', '') }} | {{ config('app.email', '') }}</div>
        </div>
        <div class="document-title">{{ __('SALES ORDER') }}</div>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-block">
                <div class="info-label">{{ __('SO Number') }}:</div>
                <div class="info-value"><strong>{{ $sale->code }}</strong></div>
                
                <div class="info-label" style="margin-top: 10px;">{{ __('Sale Date') }}:</div>
                <div class="info-value">{{ $sale->date ? $sale->date->format('F j, Y') : '-' }}</div>
                
                @if($sale->expected_delivery_date)
                <div class="info-label" style="margin-top: 10px;">{{ __('Expected Delivery') }}:</div>
                <div class="info-value">{{ $sale->expected_delivery_date->format('F j, Y') }}</div>
                @endif
                
                <div class="info-label" style="margin-top: 10px;">{{ __('Status') }}:</div>
                <div class="info-value">
                    <span class="status-badge status-{{ $sale->status }}">
                        {{ ucfirst($sale->status) }}
                    </span>
                </div>
            </div>
            
            <div class="info-block">
                <div class="info-label">{{ __('Customer') }}:</div>
                <div class="info-value">
                    <strong>{{ $sale->customer->name }}</strong><br>
                    @if($sale->customer->company_name)
                        {{ $sale->customer->company_name }}<br>
                    @endif
                    @if($sale->customer->address)
                        {{ $sale->customer->address }}<br>
                    @endif
                    @if($sale->customer->city || $sale->customer->state || $sale->customer->postal_code)
                        {{ $sale->customer->city }}
                        {{ $sale->customer->state ? ', ' . $sale->customer->state : '' }}
                        {{ $sale->customer->postal_code }}<br>
                    @endif
                    {{ $sale->customer->email }}<br>
                    {{ $sale->customer->phone_number }}
                </div>
                
                <div class="info-label" style="margin-top: 10px;">{{ __('Ship From') }}:</div>
                <div class="info-value">
                    <strong>{{ $sale->warehouse->name }}</strong><br>
                    @if($sale->warehouse->address)
                        {{ $sale->warehouse->address }}<br>
                    @endif
                    @if($sale->warehouse->city || $sale->warehouse->state || $sale->warehouse->postal_code)
                        {{ $sale->warehouse->city }}
                        {{ $sale->warehouse->state ? ', ' . $sale->warehouse->state : '' }}
                        {{ $sale->warehouse->postal_code }}<br>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($sale->reference_no || $sale->payment_terms)
    <div class="info-section">
        <div class="info-row">
            @if($sale->reference_no)
            <div class="info-block">
                <div class="info-label">{{ __('Reference Number') }}:</div>
                <div class="info-value">{{ $sale->reference_no }}</div>
            </div>
            @endif
            @if($sale->payment_terms)
            <div class="info-block">
                <div class="info-label">{{ __('Payment Terms') }}:</div>
                <div class="info-value">{{ $sale->payment_terms }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">{{ __('Product') }}</th>
                <th style="width: 10%;">{{ __('SKU') }}</th>
                <th style="width: 10%;" class="text-right">{{ __('Quantity') }}</th>
                <th style="width: 10%;">{{ __('Unit') }}</th>
                <th style="width: 15%;" class="text-right">{{ __('Unit Price') }}</th>
                <th style="width: 15%;" class="text-right">{{ __('Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sale->products ?? [] as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    {{ $item->product->name }}
                    @if($item->product->description)
                        <br><small>{{ $item->product->description }}</small>
                    @endif
                </td>
                <td>{{ $item->product->sku ?? $item->product->code }}</td>
                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->product->unit ? $item->product->unit->name : '-' }}</td>
                <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">{{ __('No items found') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals-section">
        <div class="total-row">
            <div class="total-label">{{ __('Subtotal') }}:</div>
            <div>${{ number_format($sale->subtotal, 2) }}</div>
        </div>
        
        @if($sale->discount_amount > 0)
        <div class="total-row">
            <div class="total-label">{{ __('Discount') }}:</div>
            <div>-${{ number_format($sale->discount_amount, 2) }}</div>
        </div>
        @endif
        
        @if($sale->tax_amount > 0)
        <div class="total-row">
            <div class="total-label">{{ __('Tax') }}:</div>
            <div>${{ number_format($sale->tax_amount, 2) }}</div>
        </div>
        @endif
        
        @if($sale->shipping_cost > 0)
        <div class="total-row">
            <div class="total-label">{{ __('Shipping') }}:</div>
            <div>${{ number_format($sale->shipping_cost, 2) }}</div>
        </div>
        @endif
        
        <div class="total-row grand-total">
            <div class="total-label">{{ __('Total Amount') }}:</div>
            <div>${{ number_format($sale->total_amount, 2) }}</div>
        </div>
    </div>

    <div style="clear: both;"></div>

    @if($sale->notes)
    <div class="notes-section">
        <div class="info-label">{{ __('Notes') }}:</div>
        <div class="info-value">{{ $sale->notes }}</div>
    </div>
    @endif

    @if($sale->terms_conditions)
    <div class="notes-section">
        <div class="info-label">{{ __('Terms & Conditions') }}:</div>
        <div class="info-value">{{ $sale->terms_conditions }}</div>
    </div>
    @endif

    <div class="footer">
        <p>{{ __('This is a computer-generated document. No signature is required.') }}</p>
        <p>{{ __('Generated on') }} {{ now()->format('F j, Y g:i A') }}</p>
    </div>
</body>
</html>