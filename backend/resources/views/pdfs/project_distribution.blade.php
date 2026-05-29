<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project Profit Distribution - {{ $project_name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 20px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Project Profit Distribution Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Date: {{ $date }}</div>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Project:</strong> {{ $project_name }}<br>
        <strong>Status:</strong> {{ strtoupper($status) }}<br>
        <strong>Total Capital Invested:</strong> â‚¦ {{ number_format($total_invested, 2) }}
    </div>

    <div class="section-title">Investor Capital Breakdown</div>
    <table>
        <thead>
            <tr>
                <th>Investor Member</th>
                <th class="right">Investment (â‚¦)</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($investments as $i)
            <tr>
                <td>{{ $i['member'] }}</td>
                <td class="right">{{ number_format($i['amount'], 2) }}</td>
                <td>{{ $i['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Profit Distribution History</div>
    @foreach($profit_history as $p)
    <div style="margin-top: 10px; background: #f9f9f9; padding: 10px; border: 1px solid #eee;">
        <strong>Profit Date:</strong> {{ $p['date'] }} |
        <strong>Gross:</strong> â‚¦{{ number_format($p['gross_profit'], 2) }} |
        <strong>Mgt Fee:</strong> â‚¦{{ number_format($p['management_fee'], 2) }} |
        <strong>Net for Investors:</strong> â‚¦{{ number_format($p['net_distributable'], 2) }}
    </div>
    <table>
        <thead>
            <tr>
                <th>Member</th>
                <th class="right">Payout Amount (â‚¦)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($p['payouts'] as $pay)
            <tr>
                <td>{{ $pay['member'] }}</td>
                <td class="right">{{ number_format($pay['amount'], 2) }}</td>
                <td>{{ ucfirst((string) $pay['status']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach
</body>
</html>
