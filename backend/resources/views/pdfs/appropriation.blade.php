<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Appropriation Account - {{ $year }}</title>
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
            <div class="title">Appropriation Account</div>
            <div class="muted">For the year ended {{ $year }}</div>
        </div>
        <div style="text-align:right">
            @if(!empty($user))
                <div><strong>{{ $user->name }}</strong></div>
                <div class="muted">Membership ID: {{ $user->membership_number }}</div>
            @endif
            <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <table style="margin-bottom: 12px">
        <tbody>
            <tr>
                <td>Period</td>
                <td class="right">{{ $from ?? ($year.'-01-01') }} to {{ $to ?? ($year.'-12-31') }}</td>
            </tr>
            <tr>
                <td>Surplus for the year</td>
                <td class="right">₦ {{ number_format((float) ($surplus ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Appropriation</th>
                <th class="right">Percent</th>
                <th class="right">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($appropriations ?? []) as $line)
                <tr>
                    <td>{{ $line['name'] ?? 'Appropriation' }}</td>
                    <td class="right">{{ number_format((float) ($line['percent'] ?? 0), 2) }}%</td>
                    <td class="right">₦ {{ number_format((float) ($line['amount'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="muted">No appropriation ratios configured or surplus is zero.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total Appropriations</td>
                <td></td>
                <td class="right">₦ {{ number_format((float) ($total_appropriated ?? 0), 2) }}</td>
            </tr>
            <tr>
                <td>Carried forward</td>
                <td></td>
                <td class="right">₦ {{ number_format((float) ($carried_forward ?? 0), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
