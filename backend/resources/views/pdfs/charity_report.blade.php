<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Charity Fund Report - {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .summary { margin-bottom: 20px; border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Charity Fund Report (Non-Halal Income Disposal)</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Period: {{ $from }} to {{ $to }}</div>
    </div>

    <div class="summary">
        <strong>Summary</strong><br>
        Total Inflow (Attendance Fines, etc.): â‚¦ {{ number_format($total_inflow, 2) }}<br>
        Total Outflow (Donations/Sadaqah): â‚¦ {{ number_format($total_outflow, 2) }}<br>
        <strong>Net Fund Balance: â‚¦ {{ number_format($net_balance, 2) }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Source/Purpose</th>
                <th>Note</th>
                <th class="right">Amount (â‚¦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $item)
            <tr>
                <td>{{ $item['date'] }}</td>
                <td>{{ $item['source'] }}</td>
                <td>{{ $item['note'] }}</td>
                <td class="right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
