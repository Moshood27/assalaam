<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cooperative Zakat Report - {{ $date }}</title>
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
        <div class="title">Cooperative & Member Zakat Portfolio</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Generated Date: {{ $date }}</div>
    </div>

    <div class="summary-box">
        <strong>Zakat Parameters</strong><br>
        Current Gold Price: â‚¦ {{ number_format($gold_price, 2) }} / gram<br>
        Nisab Threshold: â‚¦ {{ number_format($nisab_ngn, 2) }}<br>
        Zakat Rate: {{ $rate }}<br>
        <br>
        <strong>Cooperative's Zakat Liability (Assets)</strong><br>
        Cash & Bank: â‚¦ {{ number_format($coop_cash_balance, 2) }}<br>
        Murabahah Receivables: â‚¦ {{ number_format($coop_murabahah_receivables, 2) }}<br>
        Gold Inventory Value: â‚¦ {{ number_format($coop_gold_inventory, 2) }}<br>
        Total Zakatable Assets: â‚¦ {{ number_format($coop_zakatable_total, 2) }}<br>
        <strong>Coop Zakat Due: â‚¦ {{ number_format($coop_zakat_due, 2) }}</strong>
        <br><br>
        <strong>Collection Summary (Amanah)</strong><br>
        Total Member Zakat Collected: â‚¦ {{ number_format($total_collected_zakat, 2) }}
    </div>

    <div style="font-weight: bold; margin-top: 20px;">Member Zakat Summary</div>
    <table>
        <thead>
            <tr>
                <th>Member Name</th>
                <th>Membership ID</th>
                <th class="right">Base Wealth (â‚¦)</th>
                <th class="right">Zakat Due (â‚¦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($member_details as $m)
            <tr>
                <td>{{ $m['name'] }}</td>
                <td>{{ $m['membership_number'] }}</td>
                <td class="right">{{ number_format($m['base_wealth'], 2) }}</td>
                <td class="right">{{ number_format($m['zakat_due'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #eee;">
                <td colspan="3">Total Member Zakat Portfolio</td>
                <td class="right">â‚¦ {{ number_format($total_member_zakat_due, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
