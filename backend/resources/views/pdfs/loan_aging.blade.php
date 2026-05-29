<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan (Qard Hasan) Aging Report - {{ $date }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .overdue { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Qard Hasan & Murabahah Aging Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>As of Date: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Member</th>
                <th class="right">Original Amount (₦)</th>
                <th class="right">Repaid (₦)</th>
                <th class="right">Balance (₦)</th>
                <th class="right">Days Since Last Pymt</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loans as $l)
            <tr>
                <td>{{ $l['type'] ?? 'Loan' }}</td>
                <td>{{ $l['member'] }}</td>
                <td class="right">{{ number_format($l['principal'], 2) }}</td>
                <td class="right">{{ number_format($l['repaid'], 2) }}</td>
                <td class="right">{{ number_format($l['balance'], 2) }}</td>
                <td class="right">{{ $l['days_since_last_payment'] }}</td>
                <td class="{{ $l['status'] === 'Overdue' ? 'overdue' : '' }}">{{ $l['status'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
