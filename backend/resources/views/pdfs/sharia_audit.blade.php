<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sharia Audit Report - {{ $year }}</title>
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
        <div class="title">Sharia Audit Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Period: {{ $from }} to {{ $to }}</div>
    </div>

    <div class="summary-box">
        <div style="font-weight: bold; font-size: 14px; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">Audit Summary Overview</div>
        <table style="border: none; margin-top: 0;">
            <tr>
                <td style="border: none; width: 50%; vertical-align: top;">
                    <strong>Islamic Financing (Murabahah):</strong><br>
                    Total Contracts: {{ $murabahah['count'] }}<br>
                    Total Value: ₦{{ number_format($murabahah['total_value'], 2) }}<br>
                    Expected Profit: ₦{{ number_format($murabahah['total_profit'], 2) }}<br>
                    <br>
                    <strong>Investment Projects (Mudarabah/Musharakah):</strong><br>
                    Active/New Projects: {{ $projects['count'] }}<br>
                    Total Capital Goal: ₦{{ number_format($projects['total_capital'], 2) }}<br>
                    <br>
                    <strong>Welfare Pool (Takaful):</strong><br>
                    Settlements: {{ $takaful['count'] }}<br>
                    Total Settled: ₦{{ number_format($takaful['total_amount'], 2) }}<br>
                </td>
                <td style="border: none; width: 50%; vertical-align: top;">
                    <strong>Social Responsibility:</strong><br>
                    Charity Disbursements: {{ $charity_disbursements['count'] }}<br>
                    Total Disbursed: ₦{{ number_format($charity_disbursements['total_amount'], 2) }}<br>
                    <br>
                    <strong>Audit Metrics:</strong><br>
                    Total Audit Logs: {{ $total_audits }}<br>
                    <br>
                    <strong>Actions Breakdown:</strong><br>
                    @foreach($actions_summary as $action => $count)
                        {{ str_replace('_', ' ', ucfirst((string) $action)) }}: {{ $count }}<br>
                    @endforeach
                </td>
            </tr>
        </table>
    </div>

    <div style="margin: 20px 0; padding: 15px; border: 2px solid #059669; background-color: #ecfdf5; border-radius: 8px;">
        <h3 style="margin-top: 0; color: #059669; text-align: center;">SHARIA COMPLIANCE CERTIFICATE</h3>
        <p style="text-align: justify; line-height: 1.6;">
            We, the Sharia Advisory Board of AS-SALAAM Osogbo CICS Ltd, having reviewed the cooperative's operations,
            investments, and Murabahah contracts for the period of <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong>,
            hereby certify that based on the audit logs and internal reports presented, the activities of the
            cooperative have been conducted in accordance with the principles of Sharia.
            All identified non-halal income (if any) has been correctly channeled to the Charity Fund.
        </p>
    </div>

    <div style="font-weight: bold; margin-top: 20px;">Detailed Audit Log (Recent 50)</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Action</th>
                <th>Payload (JSON)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recent_logs as $log)
            <tr>
                <td>{{ $log['date'] }}</td>
                <td>{{ $log['action'] }}</td>
                <td><pre style="font-size: 9px;">{{ json_encode($log['payload']) }}</pre></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 40px; text-align: right;">
        <div style="border-top: 1px solid #000; display: inline-block; width: 200px; text-align: center;">
            Sharia Board Signature
        </div>
    </div>
</body>
</html>
