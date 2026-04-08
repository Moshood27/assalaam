<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Receipt</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 12px; }
        .header { display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: 800; }
        .muted { color: #6b7280; font-size: 11px; }
        .badge { display:inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 10px; font-weight: 700; background: #e5e7eb; color: #374151; }
        .badge-success { background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 10px; text-transform: uppercase; }
        .right { text-align: right; }
        .section { margin-top: 12px; }
        .footer { margin-top: 16px; font-size: 11px; color: #6b7280; }
        .total-row { font-weight: 800; background: #f9fafb; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Order Receipt</div>
            <div class="muted">Reference: {{ $order->reference }}</div>
        </div>
        <div style="text-align:right">
            <div><strong>{{ $user->name }}</strong></div>
            <div class="muted">Membership ID: {{ $user->membership_number }}</div>
            @if(!empty($branch))
                <div class="muted">Branch: {{ $branch }}</div>
            @endif
            <div class="muted">Date: {{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">₦ {{ number_format((float)$item->unit_price, 2) }}</td>
                    <td class="right">₦ {{ number_format((float)$item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3" class="right">GRAND TOTAL</td>
                    <td class="right">₦ {{ number_format((float)$order->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @php($meta = is_array($order->meta) ? $order->meta : json_decode((string)($order->meta ?? '[]'), true))
    @php($financing = $meta['financing'] ?? null)

    @if($financing)
    <div class="section" style="background: #fffbeb; border: 1px solid #fde68a; padding: 10px; border-radius: 4px;">
        <div style="font-weight: 800; text-transform: uppercase; color: #92400e; margin-bottom: 5px;">Murabaha Financing Details</div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 5px;">
            <div>Tenor: <strong>{{ $financing['months'] }} months</strong></div>
            <div>Profit Rate: <strong>{{ round(((float)($financing['profit_rate'] ?? 0)) * 100) }}%</strong></div>
            <div>Total Paid: <strong>₦ {{ number_format((float)($financing['total_paid'] ?? 0), 2) }}</strong></div>
            <div>Remaining: <strong>₦ {{ number_format((float)($financing['remaining'] ?? ($order->total_amount - ($financing['total_paid'] ?? 0))), 2) }}</strong></div>
        </div>
    </div>
    @endif

    <div class="section">
        <div class="muted uppercase font-bold">Status: {{ strtoupper(str_replace('_', ' ', $order->status)) }}</div>
        @if(!empty($meta['note']))
            <div class="muted" style="margin-top: 5px;"><strong>Note:</strong> {{ $meta['note'] }}</div>
        @endif
    </div>

    <p class="footer">Thank you for your patronage. This is a computer-generated receipt.</p>
</body>
</html>
