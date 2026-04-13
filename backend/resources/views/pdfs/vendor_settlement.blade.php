<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Settlement Report - {{ $date }}</title>
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
        <div class="title">Vendor Settlement Report</div>
        <div>At-Taqwa Osogbo CICS Ltd</div>
        <div>Generated Date: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Vendor Name</th>
                <th>Owner</th>
                <th class="right">Total Sales (₦)</th>
                <th class="right">Vendor Earnings (₦)</th>
                <th class="right">Coop Commission (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vendors as $v)
            <tr>
                <td>{{ $v['vendor_name'] }}</td>
                <td>{{ $v['owner'] }}</td>
                <td class="right">{{ number_format($v['total_sales'], 2) }}</td>
                <td class="right">{{ number_format($v['vendor_payouts'], 2) }}</td>
                <td class="right">{{ number_format($v['coop_commission'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
