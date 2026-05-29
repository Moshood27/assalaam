<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gold Savings Valuation Report - {{ $date }}</title>
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
        <div class="title">Gold Savings Valuation Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Generated Date: {{ $date }}</div>
    </div>

    <div class="summary-box">
        <strong>Market Valuation Summary</strong><br>
        Current Gold Price: ₦ {{ number_format($current_gold_price, 2) }} / gram<br>
        Total Weight Held: {{ number_format($total_weight_grams, 4) }} grams<br>
        <strong>Total Market Value: ₦ {{ number_format($total_market_value, 2) }}</strong>
    </div>

    <div style="font-weight: bold; margin-top: 20px;">Top 10 Gold Holders</div>
    <table>
        <thead>
            <tr>
                <th>Member Name</th>
                <th class="right">Weight (grams)</th>
                <th class="right">Estimated Value (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($top_holders as $h)
            <tr>
                <td>{{ $h['name'] }}</td>
                <td class="right">{{ number_format($h['weight'], 4) }}</td>
                <td class="right">{{ number_format($h['value'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
