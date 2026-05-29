<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Zakat Al-Maal Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #1e293b; font-size: 11px; line-height: 1.5; }
        .container { padding: 20px; }
        .header { margin-bottom: 30px; border-bottom: 3px solid #059669; padding-bottom: 15px; }
        .title { font-size: 20px; font-weight: 900; color: #059669; text-transform: uppercase; }
        .muted { color: #64748b; font-size: 10px; }
        .section-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.1em; margin-bottom: 10px; margin-top: 20px; }
        .card { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 20px; border: 1px solid #e2e8f0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { font-size: 9px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0; }
        .right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-blue { color: #059669; }
        .text-amber { color: #d97706; }
        .total-box { background: #059669; color: white; border-radius: 12px; padding: 20px; text-align: center; margin-top: 30px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .total-label { font-size: 10px; font-weight: 700; text-transform: uppercase; opacity: 0.9; margin-bottom: 5px; }
        .total-amount { font-size: 28px; font-weight: 900; }
        .footer { margin-top: 40px; font-size: 9px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .status-badge { display: inline-block; padding: 4px 8px; border-radius: 9999px; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .status-eligible { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table style="border:none;">
                <tr style="border:none;">
                    <td style="border:none; width: 60%; vertical-align: top;">
                        <div class="title">Zakat Al-Maal Report</div>
                        <div class="muted">Automated Wealth Assessment & Purification</div>
                    </td>
                    <td style="border:none; width: 40%; text-align: right; vertical-align: top;">
                        <div class="font-bold">{{ $user->full_name }}</div>
                        <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                        <div class="muted">Branch: {{ $branch ?? 'Main' }}</div>
                        <div class="muted">Date: {{ now()->format('F d, Y') }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="status-badge {{ $eligible ? 'status-eligible' : 'status-pending' }}">
                    {{ $eligible ? 'Eligible for Zakat' : 'Hawl in Progress' }}
                </span>
                <span class="muted" style="margin-left: 10px;">
                    Hawl Period: {{ $crossed_on ? \Carbon\Carbon::parse($crossed_on)->format('M d, Y') : 'N/A' }}
                    to {{ $eligible_on ? \Carbon\Carbon::parse($eligible_on)->format('M d, Y') : 'N/A' }}
                </span>
            </div>
        </div>

        <div class="section-title">Wealth Breakdown</div>
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Asset Category</th>
                        <th class="right">Value (NGN)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Savings & Shares</td>
                        <td class="right">₦ {{ number_format($savings + $shares, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Gold Holdings ({{ number_format($user->gold_balance ?? 0, 4) }}g)</td>
                        <td class="right">₦ {{ number_format($gold_value, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Available Wallet Balance</td>
                        <td class="right">₦ {{ number_format($wallet_balance, 2) }}</td>
                    </tr>
                    <tr class="font-bold">
                        <td style="border-top: 2px solid #e2e8f0; color: #1e293b;">Total Zakatable Wealth</td>
                        <td class="right" style="border-top: 2px solid #e2e8f0; color: #059669;">₦ {{ number_format($base, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section-title">Nisab Comparison</div>
        <div class="card">
            <table>
                <tr>
                    <td style="width: 50%;">Current Gold Nisab (85g)</td>
                    <td class="right font-bold text-amber">₦ {{ number_format($nisab, 2) }}</td>
                </tr>
                <tr>
                    <td>Surplus over Nisab</td>
                    <td class="right font-bold text-blue">₦ {{ number_format(max(0, $base - $nisab), 2) }}</td>
                </tr>
            </table>
            <p class="muted" style="margin-top: 10px;">
                * The Nisab is calculated based on the current market value of 85 grams of 24k gold (₦{{ number_format($gold_price ?? 0, 2) }}/g).
            </p>
        </div>

        <div class="total-box">
            <div class="total-label">Estimated Zakat Due (2.5%)</div>
            <div class="total-amount">₦ {{ number_format($zakat_due, 2) }}</div>
        </div>

        <div class="footer">
            <p>This report was automatically generated based on the Sharia-compliant "85g Rule" and Hawl tracking logic.</p>
            <p>Payment of Zakat is an act of worship and a religious obligation. Click "Pay Zakat" in your mobile app to fulfill this duty.</p>
            <p>&copy; {{ date('Y') }} Cooperative Management System. All Rights Reserved.</p>
        </div>
    </div>
</body>
</html>
