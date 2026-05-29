<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Zakat Portfolio - {{ $year }}</title>
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
        <div class="title">Member Zakat Portfolio Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Period: {{ $from }} to {{ $to }}</div>
    </div>

    <div class="summary-box">
        <strong>Zakat Collection Summary</strong><br>
        Total Zakat Collected: ₦ {{ number_format($total_zakat_collected, 2) }}<br>
        Total Contributing Members: {{ $members_count }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Member Name</th>
                <th>Member #</th>
                <th class="right">Total Paid (₦)</th>
                <th>Last Payment</th>
                <th class="right">Tx Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($portfolio as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['membership_number'] }}</td>
                <td class="right">{{ number_format($item['total_paid'], 2) }}</td>
                <td>{{ $item['last_payment_date'] }}</td>
                <td class="right">{{ $item['count'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
