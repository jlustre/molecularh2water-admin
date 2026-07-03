<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Quote {{ $quotation->quote_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .muted { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px; text-align: left; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        .totals { margin-top: 16px; width: 280px; margin-left: auto; }
        .totals td { border: none; padding: 4px 8px; }
        .totals .grand { font-size: 14px; font-weight: bold; border-top: 2px solid #0f766e; }
        .section { margin-top: 20px; }
        .section h3 { font-size: 11px; text-transform: uppercase; color: #64748b; margin-bottom: 6px; }
    </style>
</head>
<body>
    <h1>Quotation {{ $quotation->quote_number }}</h1>
    <p class="muted">Prepared {{ $quotation->created_at->format('F j, Y') }}</p>

    <div class="section">
        <h3>Prepared For</h3>
        <p>
            <strong>{{ $quotation->lead->fullName() }}</strong><br>
            @if ($quotation->lead->email) {{ $quotation->lead->email }}<br> @endif
            @if ($quotation->lead->phone) {{ $quotation->lead->phone }}<br> @endif
            @if ($quotation->lead->address) {{ $quotation->lead->address }} @endif
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($quotation->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td>${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td align="right">${{ number_format($quotation->subtotal, 2) }}</td></tr>
        @if ($quotation->discount_amount > 0)
            <tr><td>Discount</td><td align="right">-${{ number_format($quotation->discount_amount, 2) }}</td></tr>
        @endif
        @if ($quotation->tax_amount > 0)
            <tr><td>Tax</td><td align="right">${{ number_format($quotation->tax_amount, 2) }}</td></tr>
        @endif
        @if ($quotation->shipping_amount > 0)
            <tr><td>Shipping</td><td align="right">${{ number_format($quotation->shipping_amount, 2) }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td align="right">${{ number_format($quotation->total, 2) }}</td></tr>
    </table>

    @if ($quotation->valid_until)
        <p class="section muted">Valid until {{ $quotation->valid_until->format('F j, Y') }}</p>
    @endif

    @if ($quotation->warranty_notes)
        <div class="section">
            <h3>Warranty</h3>
            <p>{{ $quotation->warranty_notes }}</p>
        </div>
    @endif

    @if ($quotation->financing_notes)
        <div class="section">
            <h3>Financing</h3>
            <p>{{ $quotation->financing_notes }}</p>
        </div>
    @endif

    @if ($quotation->payment_plan_notes)
        <div class="section">
            <h3>Payment Plan</h3>
            <p>{{ $quotation->payment_plan_notes }}</p>
        </div>
    @endif

    @if ($quotation->notes)
        <div class="section">
            <h3>Notes</h3>
            <p>{{ $quotation->notes }}</p>
        </div>
    @endif
</body>
</html>
