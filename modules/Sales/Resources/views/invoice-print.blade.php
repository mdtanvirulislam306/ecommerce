<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice['number'] }} — Invoice</title>
    <style>
        :root { --navy: #0f2744; --muted: #64748b; --line: #e2e8f0; --teal: #0f766e; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--navy); font-family: "Segoe UI", system-ui, sans-serif; background: #f8fafc; }
        .wrap { max-width: 800px; margin: 0 auto; padding: 28px 20px 48px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
        .toolbar button, .toolbar a {
            border: 1px solid var(--line); background: #fff; color: var(--navy);
            border-radius: 8px; padding: 8px 14px; font-size: 13px; font-weight: 600;
            cursor: pointer; text-decoration: none;
        }
        .toolbar button.primary { background: var(--teal); color: #fff; border-color: var(--teal); }
        .sheet { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 28px; }
        h1 { font-size: 22px; margin: 0 0 4px; }
        .muted { color: var(--muted); font-size: 13px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 24px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); border-bottom: 1px solid var(--line); padding: 8px 6px; }
        td { border-bottom: 1px solid #f1f5f9; padding: 10px 6px; }
        .right { text-align: right; }
        .totals { margin-top: 16px; margin-left: auto; width: 240px; font-size: 13px; }
        .totals div { display: flex; justify-content: space-between; padding: 4px 0; }
        .totals .grand { font-weight: 700; font-size: 16px; border-top: 1px solid var(--line); margin-top: 6px; padding-top: 8px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet { border: none; border-radius: 0; padding: 0; }
            .wrap { padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="toolbar">
        <button class="primary" type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('sales.invoices.show', $invoice['id']) }}">Back to invoice</a>
        @if (!empty($invoice['sales_order_id']))
            <a href="{{ route('sales.orders.show', $invoice['sales_order_id']) }}">Open order</a>
        @endif
    </div>

    <div class="sheet">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;">
            <div>
                <h1>Invoice {{ $invoice['number'] }}</h1>
                <p class="muted">Status: {{ $invoice['status_label'] }}</p>
            </div>
            <div class="right muted">
                <div>{{ config('app.name') }}</div>
                @if (!empty($invoice['due_date']))
                    <div>Due {{ $invoice['due_date'] }}</div>
                @endif
            </div>
        </div>

        <div class="grid">
            <div>
                <div class="muted">Bill to</div>
                <div style="font-weight:600;margin-top:4px;">{{ $invoice['customer_name'] }}</div>
                @if (!empty($invoice['customer_email']))
                    <div class="muted">{{ $invoice['customer_email'] }}</div>
                @endif
                @if (!empty($invoice['customer_phone']))
                    <div class="muted">{{ $invoice['customer_phone'] }}</div>
                @endif
            </div>
            <div class="right">
                <div class="muted">Amounts</div>
                <div style="margin-top:4px;">Total {{ $invoice['currency'] }} {{ number_format((float) $invoice['grand_total'], 2) }}</div>
                <div class="muted">Paid {{ number_format((float) $invoice['amount_paid'], 2) }}</div>
                <div class="muted">Due {{ number_format((float) $invoice['amount_due'], 2) }}</div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Item</th>
                <th>SKU</th>
                <th class="right">Qty</th>
                <th class="right">Unit</th>
                <th class="right">Total</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($invoice['items'] as $item)
                <tr>
                    <td>{{ $item['name'] }}</td>
                    <td class="muted">{{ $item['sku'] ?: '—' }}</td>
                    <td class="right">{{ number_format((float) $item['quantity'], 2) }}</td>
                    <td class="right">{{ number_format((float) $item['unit_price'], 2) }}</td>
                    <td class="right">{{ number_format((float) $item['line_total'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div><span class="muted">Subtotal</span><span>{{ number_format((float) $invoice['grand_total'], 2) }}</span></div>
            <div class="grand"><span>Grand total</span><span>{{ $invoice['currency'] }} {{ number_format((float) $invoice['grand_total'], 2) }}</span></div>
        </div>

        @if (!empty($invoice['notes']))
            <p class="muted" style="margin-top:24px;">Notes: {{ $invoice['notes'] }}</p>
        @endif
    </div>
</div>
@if ($autoprint)
    <script>window.addEventListener('load', () => window.print());</script>
@endif
</body>
</html>
