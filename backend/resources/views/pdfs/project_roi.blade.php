<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Project ROI Report - {{ $date }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .total-row { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Project Investment & ROI Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Generated Date: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Project Name</th>
                <th>Status</th>
                <th class="right">Invested (₦)</th>
                <th class="right">Gross Profit (₦)</th>
                <th class="right">Coop Fee (₦)</th>
                <th class="right">Distributable (₦)</th>
                <th class="right">ROI %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($projects as $p)
            <tr>
                <td>{{ $p['project_name'] }}</td>
                <td>{{ $p['status'] }}</td>
                <td class="right">{{ number_format($p['capital_invested'], 2) }}</td>
                <td class="right">{{ number_format($p['gross_profit'], 2) }}</td>
                <td class="right">{{ number_format($p['coop_management_fee'], 2) }}</td>
                <td class="right">{{ number_format($p['net_for_investors'], 2) }}</td>
                <td class="right">{{ $p['roi_percent'] }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
