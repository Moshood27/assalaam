<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Financial Statements - {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 9px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .right { text-align: right; }
        .section-title { font-size: 14px; font-weight: 800; margin: 12px 0 6px; border-bottom: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Financial Statements</div>
                    <div class="muted">For the year ended {{ $year }}</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    @if(!empty($user))
                        <div><strong>{{ $user->name }}</strong></div>
                        <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @endif
                    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-title">Income and Expenditure</div>
    @php($ie = $income_expenditure ?? [])
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" class="muted">Income</td>
            </tr>
            @foreach(($ie['income'] ?? []) as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td class="right">₦ {{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total Income</strong></td>
                <td class="right"><strong>₦ {{ number_format((float)($ie['total_income'] ?? 0), 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="2" class="muted">Expenses</td>
            </tr>
            @foreach(($ie['expenses'] ?? []) as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td class="right">₦ {{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total Expenses</strong></td>
                <td class="right"><strong>₦ {{ number_format((float)($ie['total_expense'] ?? 0), 2) }}</strong></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td>Surplus / (Deficit)</td>
                <td class="right">₦ {{ number_format((float)($ie['surplus'] ?? 0), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="section-title">Balance Sheet</div>
    @php($bs = $balance_sheet ?? [])
    <table>
        <thead>
            <tr>
                <th>Assets</th>
                <th class="right">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($bs['assets'] ?? []) as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td class="right">₦ {{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total Assets</td>
                <td class="right">₦ {{ number_format((float)($bs['total_assets'] ?? 0), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table>
        <thead>
            <tr>
                <th>Liabilities and Equity</th>
                <th class="right">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($bs['liabilities'] ?? []) as $row)
                <tr>
                    <td>{{ $row['name'] ?? '' }}</td>
                    <td class="right">₦ {{ number_format((float)($row['amount'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total Liabilities & Equity</td>
                <td class="right">₦ {{ number_format((float)($bs['total_liabilities_and_equity'] ?? 0), 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
