<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Loan Analysis Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 8px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .right { text-align: right; }
        .center { text-align: center; }
        .section-title { font-size: 14px; font-weight: 800; margin: 20px 0 10px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .summary-box { background: #f9fafb; border: 1px solid #e5e7eb; padding: 10px; margin-bottom: 20px; }
        .summary-grid { display: table; width: 100%; }
        .summary-item { display: table-cell; width: 25%; padding: 5px; }
        .summary-label { font-size: 8px; color: #6b7280; text-transform: uppercase; font-weight: bold; }
        .summary-value { font-size: 14px; font-weight: 900; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 60%; vertical-align: top;">
                    <div class="title">{{ $cooperative_name }}</div>
                    <div class="muted">LOAN ANALYSIS REPORT AS AT {{ strtoupper($month) }} {{ $year }}</div>
                </td>
                <td style="border:none; width: 40%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->full_name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @if(!empty($user->branch))
                        <div class="muted">Branch: {{ $user->branch->name }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Borrowed</div>
                <div class="summary-value">₦ {{ number_format($totals['loan_granted'], 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Repaid</div>
                <div class="summary-value">₦ {{ number_format($totals['amount_repaid'], 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Expected to Date</div>
                <div class="summary-value">₦ {{ number_format($totals['expected_amount_to_pay'], 2) }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Outstanding Balance</div>
                <div class="summary-value" style="color: #e11d48;">₦ {{ number_format($totals['loan_balance'], 2) }}</div>
            </div>
        </div>
    </div>

    <div class="section-title">Loan Details</div>
    <table>
        <thead>
            <tr>
                <th style="width: 30px;">S/N</th>
                <th>Date Granted</th>
                <th>Loan Granted</th>
                <th>Amount Repaid</th>
                <th>Expected to Pay</th>
                <th>Amount Defaulted</th>
                <th>Loan Balance</th>
                <th>Savings Balance</th>
                <th>Period of Default</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td class="center">{{ $row['sn'] }}</td>
                    <td>{{ $row['date_granted'] instanceof \Carbon\Carbon ? $row['date_granted']->format('d-m-Y') : $row['date_granted'] }}</td>
                    <td class="right">{{ number_format($row['loan_granted'], 2) }}</td>
                    <td class="right">{{ number_format($row['amount_repaid'], 2) }}</td>
                    <td class="right">{{ number_format($row['expected_amount_to_pay'], 2) }}</td>
                    <td class="right">{{ number_format($row['amount_defaulted'], 2) }}</td>
                    <td class="right">{{ number_format($row['loan_balance'], 2) }}</td>
                    <td class="right">{{ number_format($row['savings_balance'], 2) }}</td>
                    <td>{{ $row['period_of_default'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="center">TOTAL</td>
                <td class="right">{{ number_format($totals['loan_granted'], 2) }}</td>
                <td class="right">{{ number_format($totals['amount_repaid'], 2) }}</td>
                <td class="right">{{ number_format($totals['expected_amount_to_pay'], 2) }}</td>
                <td class="right">{{ number_format($totals['amount_defaulted'], 2) }}</td>
                <td class="right">{{ number_format($totals['loan_balance'], 2) }}</td>
                <td class="right">{{ number_format($totals['savings_balance'], 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <p class="muted" style="margin-top:20px">
        Note: This analysis report is based on records as of {{ $month }} {{ $year }}.
        The "Savings Balance" reflects the total contributions in your primary savings and share capital accounts.
    </p>

    <p class="muted">Generated on {{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
