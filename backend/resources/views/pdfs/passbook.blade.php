<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cooperative Passbook</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 9px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .right { text-align: right; }
        .center { text-align: center; }
        .section-title { font-size: 14px; font-weight: 800; margin: 20px 0 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Passbook Statement</div>
                    <div class="muted">Year: {{ $year }}</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->full_name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @if(!empty($branch))
                        <div class="muted">Branch: {{ $branch }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Yearly Summary Matrix</div>
    <table style="font-size: 9px;">
        <thead>
            <tr>
                <th>Scheme</th>
                <th class="center">BF</th>
                @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $m)
                    <th class="center">{{ $m }}</th>
                @endforeach
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($matrix as $row)
                <tr>
                    <td><strong>{{ $row['scheme_name'] }}</strong></td>
                    <td class="center">{{ $row['bf'] > 0 ? number_format($row['bf'], 0) : '-' }}</td>
                    @foreach($row['months'] as $val)
                        <td class="center">{{ $val > 0 ? number_format($val, 0) : '-' }}</td>
                    @endforeach
                    <td class="right"><strong>{{ number_format($row['total'], 2) }}</strong></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td><strong>GRAND TOTAL</strong></td>
                <td class="center">{{ number_format($bf_total, 2) }}</td>
                <td colspan="12"></td>
                <td class="right">{{ number_format($grand_total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Transaction Details</div>
    <table>
        <thead>
            <tr>
                <th style="width: 18%">Date</th>
                <th>Scheme</th>
                <th style="width: 25%">Reference</th>
                <th class="right" style="width: 15%">Units</th>
                <th class="right" style="width: 15%">Amount ()</th>
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
                    <td class="right">{{ $c->units ? number_format((float)$c->units, 4).(str_contains(strtolower(optional($c->scheme)->name), 'gold') ? 'g' : '') : '—' }}</td>
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
                <td colspan="4">Total</td>
                <td class="right">{{ number_format((float) $total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:12px">Generated on {{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
