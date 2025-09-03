<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Stock Transfer') }} {{ $transfer->display_code }} - {{ __('Print') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 18px;
            color: #666;
            font-weight: normal;
        }
        
        .transfer-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-section {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-row {
            margin-bottom: 8px;
        }
        
        .info-row strong {
            display: inline-block;
            min-width: 120px;
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-draft { background-color: #6c757d; color: white; }
        .status-approved { background-color: #17a2b8; color: white; }
        .status-in_transit { background-color: #ffc107; color: black; }
        .status-completed { background-color: #28a745; color: white; }
        .status-cancelled { background-color: #dc3545; color: white; }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .products-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .notes-section {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .notes-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        
        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
            margin-top: 40px;
        }
        
        .signature-box {
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
        }
        
        .signature-box .title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .signature-box .name {
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">{{ __('Print Document') }}</button>
    
    <div class="header">
        <h1>{{ __('Stock Transfer') }}</h1>
        <h2>{{ __('Transfer ID') }}: {{ $transfer->display_code }}</h2>
    </div>
    
    <div class="transfer-info">
        <div class="info-section">
            <h3>{{ __('Transfer Details') }}</h3>
            <div class="info-row">
                <strong>{{ __('Date') }}:</strong>
                {{ $transfer->transfer_date ? $transfer->transfer_date->format('Y-m-d') : __('N/A') }}
            </div>
            <div class="info-row">
                <strong>{{ __('Reference No.') }}:</strong>
                {{ $transfer->reference_no ?: __('N/A') }}
            </div>
            <div class="info-row">
                <strong>{{ __('Code') }}:</strong>
                {{ $transfer->code ?: __('N/A') }}
            </div>
            <div class="info-row">
                <strong>{{ __('Status') }}:</strong>
                <span class="status-badge status-{{ str_replace(' ', '_', strtolower($transfer->status)) }}">
                    {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
                </span>
            </div>
        </div>
        
        <div class="info-section">
            <h3>{{ __('Warehouse Information') }}</h3>
            <div class="info-row">
                <strong>{{ __('From') }}:</strong>
                {{ $transfer->sourceWarehouse->name ?? __('N/A') }}
            </div>
            <div class="info-row">
                <strong>{{ __('To') }}:</strong>
                {{ $transfer->destinationWarehouse->name ?? __('N/A') }}
            </div>
            @if($transfer->shipping_cost > 0)
            <div class="info-row">
                <strong>{{ __('Shipping Cost') }}:</strong>
                {{ number_format($transfer->shipping_cost, 2) }}
            </div>
            @endif
        </div>
    </div>
    
    @if($transfer->notes || $transfer->shipping_notes || $transfer->receiving_notes || $transfer->cancellation_reason)
    <div class="notes-section">
        <h3>{{ __('Notes') }}</h3>
        @if($transfer->notes)
        <div class="info-row">
            <strong>{{ __('General Notes') }}:</strong>
            {{ $transfer->notes }}
        </div>
        @endif
        @if($transfer->shipping_notes)
        <div class="info-row">
            <strong>{{ __('Shipping Notes') }}:</strong>
            {{ $transfer->shipping_notes }}
        </div>
        @endif
        @if($transfer->receiving_notes)
        <div class="info-row">
            <strong>{{ __('Receiving Notes') }}:</strong>
            {{ $transfer->receiving_notes }}
        </div>
        @endif
        @if($transfer->cancellation_reason)
        <div class="info-row">
            <strong>{{ __('Cancellation Reason') }}:</strong>
            {{ $transfer->cancellation_reason }}
        </div>
        @endif
    </div>
    @endif
    
    <h3>{{ __('Products') }}</h3>
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 30%">{{ __('Product Name') }}</th>
                <th style="width: 15%">{{ __('SKU') }}</th>
                <th style="width: 12%">{{ __('Quantity') }}</th>
                <th style="width: 10%">{{ __('Unit') }}</th>
                <th style="width: 28%">{{ __('Notes') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfer->products as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->name ?? __('N/A') }}</td>
                <td>{{ $item->product->sku ?? __('N/A') }}</td>
                <td>{{ number_format($item->quantity, 2) }}</td>
                <td>{{ $item->product->unit->code ?? __('N/A') }}</td>
                <td>{{ $item->notes ?: __('N/A') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #666;">{{ __('No products found') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="transfer-info">
        <div class="info-section">
            <h3>{{ __('Timeline') }}</h3>
            <div class="info-row">
                <strong>{{ __('Created') }}:</strong>
                {{ $transfer->created_at->format('Y-m-d H:i') }}
                @if($transfer->createdBy)
                    by {{ $transfer->createdBy->name }}
                @endif
            </div>
            @if($transfer->approved_at)
            <div class="info-row">
                <strong>{{ __('Approved') }}:</strong>
                {{ $transfer->approved_at->format('Y-m-d H:i') }}
                @if($transfer->approvedBy)
                    by {{ $transfer->approvedBy->name }}
                @endif
            </div>
            @endif
            @if($transfer->shipped_at)
            <div class="info-row">
                <strong>{{ __('Shipped') }}:</strong>
                {{ $transfer->shipped_at->format('Y-m-d H:i') }}
                @if($transfer->shippedBy)
                    by {{ $transfer->shippedBy->name }}
                @endif
            </div>
            @endif
            @if($transfer->received_at)
            <div class="info-row">
                <strong>{{ __('Received') }}:</strong>
                {{ $transfer->received_at->format('Y-m-d H:i') }}
                @if($transfer->receivedBy)
                    by {{ $transfer->receivedBy->name }}
                @endif
            </div>
            @endif
            @if($transfer->cancelled_at)
            <div class="info-row">
                <strong>{{ __('Cancelled') }}:</strong>
                {{ $transfer->cancelled_at->format('Y-m-d H:i') }}
                @if($transfer->cancelledBy)
                    by {{ $transfer->cancelledBy->name }}
                @endif
            </div>
            @endif
        </div>
        
        <div class="info-section">
            <h3>{{ __('Summary') }}</h3>
            <div class="info-row">
                <strong>{{ __('Total Items') }}:</strong>
                {{ $transfer->products->count() }}
            </div>
            <div class="info-row">
                <strong>{{ __('Total Quantity') }}:</strong>
                {{ number_format($transfer->products->sum('quantity'), 2) }}
            </div>
            @if($transfer->expected_arrival_date)
            <div class="info-row">
                <strong>{{ __('Expected Arrival') }}:</strong>
                {{ $transfer->expected_arrival_date->format('Y-m-d') }}
            </div>
            @endif
        </div>
    </div>
    
    <div class="signatures">
        <div class="signature-box">
            <div class="title">{{ __('Prepared By') }}</div>
            <div class="name">{{ $transfer->createdBy->name ?? __('N/A') }}</div>
            <div style="margin-top: 30px;">_________________</div>
        </div>
        
        <div class="signature-box">
            <div class="title">{{ __('Approved By') }}</div>
            <div class="name">{{ $transfer->approvedBy->name ?? __('N/A') }}</div>
            <div style="margin-top: 30px;">_________________</div>
        </div>
        
        <div class="signature-box">
            <div class="title">{{ __('Received By') }}</div>
            <div class="name">{{ $transfer->receivedBy->name ?? __('N/A') }}</div>
            <div style="margin-top: 30px;">_________________</div>
        </div>
    </div>
    
    <div class="footer">
        <p>{{ __('Generated on') }} {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>{{ __('Stock Transfer Document') }} - {{ config('app.name') }}</p>
    </div>
</body>
</html>