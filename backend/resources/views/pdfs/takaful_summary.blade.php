<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Takaful Pool Report - {{ $date }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .summary-box { border: 1px solid #ddd; padding: 10px; margin-bottom: 20px; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Takaful (Welfare) Pool Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>As of Date: {{ $date }}</div>
    </div>

    <div class="summary-box">
        <strong>Community Insurance Fund Health</strong><br>
        Total Contributions: ₦ {{ number_format($total_contributions, 2) }}<br>
        Total Claims Paid: ₦ {{ number_format($total_claims_paid, 2) }}<br>
        <strong>Net Pool Balance: ₦ {{ number_format($net_pool_balance, 2) }}</strong>
    </div>

    <div style="font-weight: bold; margin-top: 20px;">Recent Pool Activity</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Member</th>
                <th>Type</th>
                <th class="right">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recent_activity as $a)
            <tr>
                <td>{{ $a['date'] }}</td>
                <td>{{ $a['member'] }}</td>
                <td>{{ $a['type'] }}</td>
                <td class="right" style="color: {{ $a['amount'] < 0 ? 'red' : 'green' }}">
                    {{ number_format($a['amount'], 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
