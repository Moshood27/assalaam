<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Annual Dividend Statement</title>
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
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Annual Dividend Statement</div>
                    <div class="muted">Year: {{ $year }}</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->full_name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @if(optional($user->branch)->name)
                        <div class="muted">Branch: {{ $user->branch->name }}</div>
                    @endif
                    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-bottom: 12px">
        <tbody>
            <tr>
                <td>Total Savings ({{ $year }})</td>
                <td class="right">₦ {{ number_format((float) $total_savings, 2) }}</td>
            </tr>
            <tr>
                <td>Dividend Rate</td>
                <td class="right">{{ number_format((float) ($rate * 100), 2) }}%</td>
            </tr>
            <tr>
                <td>Estimated Dividend</td>
                <td class="right">₦ {{ number_format((float) $dividend, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top:12px">Generated on {{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
