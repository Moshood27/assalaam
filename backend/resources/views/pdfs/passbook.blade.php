<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cooperative Passbook</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 12px; }
        .header { display:flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .title { font-size: 18px; font-weight: 800; }
        .muted { color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 10px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="title">Financial Statement (Passbook)</div>
            <div class="muted">Year: {{ $year }}</div>
        </div>
        <div style="text-align:right">
            <div><strong>{{ $user->name }}</strong></div>
            <div class="muted">Membership ID: {{ $user->membership_number }}</div>
            @if(!empty($branch))
                <div class="muted">Branch: {{ $branch }}</div>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 18%">Date</th>
                <th>Scheme</th>
                <th style="width: 30%">Reference</th>
                <th class="right" style="width: 18%">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @forelse($contributions as $c)
                @php $amt = (float) $c->amount; $total += $amt; @endphp
                <tr>
                    <td>{{ optional($c->created_at)->format('Y-m-d H:i') }}</td>
                    <td>{{ optional($c->scheme)->name ?? '—' }}</td>
                    <td>{{ $c->reference }}</td>
                    <td class="right">{{ number_format($amt, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No contributions found.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="right">{{ number_format((float) $total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:12px">Generated on {{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
