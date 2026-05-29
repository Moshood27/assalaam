<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Savings Ledger - {{ $membership_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Member Savings Ledger (Detailed)</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Date Generated: {{ $date }}</div>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Member Name:</strong> {{ $member_name }}<br>
        <strong>Membership Number:</strong> {{ $membership_number }}<br>
        <strong>Current Savings:</strong> ₦ {{ number_format($current_savings, 2) }}<br>
        <strong>Current Shares:</strong> ₦ {{ number_format($current_shares, 2) }}<br>
        <strong>Current Gold:</strong> {{ number_format($current_gold, 4) }}g<br>
        <strong>Total Takaful Paid:</strong> ₦ {{ number_format($total_takaful_paid, 2) }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Scheme</th>
                <th>Type</th>
                <th class="right">Amount (₦)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($history as $h)
            <tr>
                <td>{{ $h['date'] }}</td>
                <td>{{ $h['scheme'] }}</td>
                <td>{{ ucfirst((string) $h['type']) }}</td>
                <td class="right">{{ number_format($h['amount'], 2) }}</td>
                <td>{{ ucfirst((string) $h['status']) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
