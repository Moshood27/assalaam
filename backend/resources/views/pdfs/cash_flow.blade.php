<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash Flow Statement - {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
        .section-header { background-color: #eee; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Statement of Cash Flows</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>For the Period: {{ $from }} to {{ $to }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Activities</th>
                <th class="right">Amount (â‚¦)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="section-header">
                <td colspan="2">Cash flows from operating activities</td>
            </tr>
            @foreach($operating['inflows'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="right">{{ number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            @foreach($operating['outflows'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="right">({{ number_format($item['amount'], 2) }})</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Net cash provided by operating activities</td>
                <td class="right">{{ number_format($operating['net'], 2) }}</td>
            </tr>

            <tr class="section-header">
                <td colspan="2">Cash flows from investing activities</td>
            </tr>
            @foreach($investing['items'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="right">{{ $item['amount'] < 0 ? '(' . number_format(abs($item['amount']), 2) . ')' : number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Net cash used in investing activities</td>
                <td class="right">{{ number_format($investing['net'], 2) }}</td>
            </tr>

            <tr class="section-header">
                <td colspan="2">Cash flows from financing activities</td>
            </tr>
            @foreach($financing['items'] as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td class="right">{{ $item['amount'] < 0 ? '(' . number_format(abs($item['amount']), 2) . ')' : number_format($item['amount'], 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>Net cash from financing activities</td>
                <td class="right">{{ number_format($financing['net'], 2) }}</td>
            </tr>

            <tr class="total-row" style="background-color: #111827; color: white;">
                <td>NET INCREASE / (DECREASE) IN CASH</td>
                <td class="right">â‚¦ {{ number_format($net_increase, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
