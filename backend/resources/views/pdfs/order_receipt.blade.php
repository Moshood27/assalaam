<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Order Receipt</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        .badge { display:inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 800; text-transform: uppercase; background: #e5e7eb; color: #374151; }
        .badge-success { background: #d1fae5; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
        .section { margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
        .total-row { font-weight: 800; background: #f9fafb; }
        .financing-box { background: #fffbeb; border: 1px solid #fde68a; padding: 10px; border-radius: 4px; }
        .financing-title { font-weight: 800; text-transform: uppercase; color: #92400e; margin-bottom: 5px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Order Receipt</div>
                    <div class="muted">Reference: {{ $order->reference }}</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @if(!empty($branch))
                        <div class="muted">Branch: {{ $branch }}</div>
                    @endif
                    <div class="muted">Date: {{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
                </td>
            </tr>
        </table>
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
    <div class="section financing-box">
        <div class="financing-title">Murabaha Financing Details</div>
        <table style="border:none; margin-top: 0;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; padding: 2px 0;">Tenor: <strong>{{ $financing['months'] }} months</strong></td>
                <td style="border:none; width: 50%; padding: 2px 0;">Profit Rate: <strong>{{ round(((float)($financing['profit_rate'] ?? 0)) * 100) }}%</strong></td>
            </tr>
            <tr style="border:none;">
                <td style="border:none; width: 50%; padding: 2px 0;">Total Paid: <strong>₦ {{ number_format((float)($financing['total_paid'] ?? 0), 2) }}</strong></td>
                <td style="border:none; width: 50%; padding: 2px 0;">Remaining: <strong>₦ {{ number_format((float)($financing['remaining'] ?? ($order->total_amount - ($financing['total_paid'] ?? 0))), 2) }}</strong></td>
            </tr>
        </table>
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
