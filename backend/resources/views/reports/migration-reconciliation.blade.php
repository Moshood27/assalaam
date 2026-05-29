<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Migration Reconciliation Report</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #064e3b; padding-bottom: 10px; }
        .header h1 { color: #064e3b; margin: 0; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin-bottom: 25px; }
        .section-title { font-weight: bold; background: #f0fdf4; padding: 5px 10px; border-left: 4px solid #059669; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th, table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        table th { background-color: #f9fafb; font-weight: bold; }
        .total-row { font-weight: bold; background-color: #f0fdf4; }
        .footer { margin-top: 50px; }
        .signature-grid { width: 100%; margin-top: 40px; }
        .signature-box { width: 45%; border-top: 1px solid #333; text-align: center; padding-top: 5px; display: inline-block; }
        .spacer { width: 10%; display: inline-block; }
        .summary-box { background: #064e3b; color: white; padding: 15px; border-radius: 8px; margin-top: 20px; }
        .summary-box h2 { margin: 0; font-size: 18px; }
        .summary-box p { margin: 10px 0 0 0; font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>assalaam Cooperative</h1>
        <p>System Migration Reconciliation Report</p>
        <p>Date: {{ $date }}</p>
    </div>

    <div class="section">
        <div class="section-title">Financial Summary (Digital Point of Truth)</div>
        <table>
            <thead>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Members Migrated</td>
                    <td>{{ number_format($memberCount) }}</td>
                </tr>
                <tr>
                    <td>Total Wallet Balance (Credit)</td>
                    <td>₦ {{ number_format($totalWallet, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Savings (Ordinary)</td>
                    <td>₦ {{ number_format($totalSavings, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Shares Capital</td>
                    <td>₦ {{ number_format($totalShares, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Digital Gold</td>
                    <td>{{ number_format($totalGold, 4) }} g</td>
                </tr>
                <tr>
                    <td>Total Outstanding Fines</td>
                    <td>₦ {{ number_format($totalFines, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Total Financial Liabilities (Wallet + Savings + Shares + Others + Takaful)</td>
                    <td>₦ {{ number_format($totalWallet + $totalSavings + $totalShares + $otherFunds + $takafulFunds, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Breakdown of Other Funds & Schemes</div>
        <table>
            <thead>
                <tr>
                    <th>Scheme / Fund</th>
                    <th>Total Migrated Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Takaful Contribution</td>
                    <td>₦ {{ number_format($takafulFunds, 2) }}</td>
                </tr>
                <tr>
                    <td>Building Fund</td>
                    <td>₦ {{ number_format($buildingBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Development Fund</td>
                    <td>₦ {{ number_format($developmentFundBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>AGM Fund</td>
                    <td>₦ {{ number_format($agmBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Loan Repayment Fund</td>
                    <td>₦ {{ number_format($loanRepaymentBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Fine Account</td>
                    <td>₦ {{ number_format($fineBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Welfare Fund</td>
                    <td>₦ {{ number_format($welfareBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Lateness Fees</td>
                    <td>₦ {{ number_format($latenessBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Stationery Fees</td>
                    <td>₦ {{ number_format($stationeryBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Loan Form Fees</td>
                    <td>₦ {{ number_format($loanFormBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>ID Card Fees</td>
                    <td>₦ {{ number_format($idCardBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Emergency Fund</td>
                    <td>₦ {{ number_format($emergencyBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Entrance Fees</td>
                    <td>₦ {{ number_format($entranceBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>H Savings</td>
                    <td>₦ {{ number_format($hSavingsBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Investment Fund</td>
                    <td>₦ {{ number_format($investmentBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Group Savings</td>
                    <td>₦ {{ number_format($groupSavingsBalance, 2) }}</td>
                </tr>
                <tr>
                    <td>Miscellaneous (Others)</td>
                    <td>₦ {{ number_format($othersBalance, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Sub-Total (Other Funds + Takaful)</td>
                    <td>₦ {{ number_format($otherFunds + $takafulFunds, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Loan Asset Summary</div>
        <table>
            <thead>
                <tr>
                    <th>Loan Metric</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Active Migrated Loans</td>
                    <td>{{ number_format($loanCount) }}</td>
                </tr>
                <tr>
                    <td>Total Principal Migrated</td>
                    <td>₦ {{ number_format($totalLoans, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Repaid to Date</td>
                    <td>₦ {{ number_format($paidLoans, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>Outstanding Loan Asset (Net)</td>
                    <td>₦ {{ number_format($remainingLoans, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="summary-box">
        <h2>Grand Total Reconciliation</h2>
        <p>Total Member Wealth (Naira): ₦ {{ number_format($totalWallet + $totalSavings + $totalShares + $otherFunds + $takafulFunds, 2) }}</p>
        <p style="font-size: 16px; margin-top: 5px;">Total Gold Weight: {{ number_format($totalGold, 4) }} g</p>
    </div>

    <div class="footer">
        <p>This document serves as the official reconciliation report for the system migration. By signing below, the authorities confirm that the digital balances match the physical paper passbooks and excel master sheets.</p>

        <table style="border: none; margin-top: 40px;">
            <tr>
                <td style="border: none; width: 45%; border-top: 1px solid #000; text-align: center;">
                    <strong>Treasurer</strong><br>
                    Name: _______________________
                </td>
                <td style="border: none; width: 10%;"></td>
                <td style="border: none; width: 45%; border-top: 1px solid #000; text-align: center;">
                    <strong>Chairman</strong><br>
                    Name: _______________________
                </td>
            </tr>
            <tr>
                <td style="border: none; padding-top: 20px; text-align: center;">Date: ____/____/20____</td>
                <td style="border: none;"></td>
                <td style="border: none; padding-top: 20px; text-align: center;">Date: ____/____/20____</td>
            </tr>
        </table>
    </div>
</body>
</html>
