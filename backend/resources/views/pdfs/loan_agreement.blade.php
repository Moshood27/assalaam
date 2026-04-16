<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Loan Agreement - {{ $loan->qard_id_string }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: 800; text-transform: uppercase; }
        .subtitle { font-size: 12px; font-weight: 600; margin-top: 5px; }
        .section { margin-top: 15px; }
        .section-title { font-weight: 800; text-decoration: underline; margin-bottom: 5px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 800; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
        .footer { margin-top: 30px; }
        .signature-box { width: 45%; display: inline-block; vertical-align: top; margin-top: 20px; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; font-weight: 800; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">COOPERATIVE MULTIPURPOSE SOCIETY</div>
        <div class="subtitle">LOAN AGREEMENT & REPAYMENT SCHEDULE</div>
        <div style="margin-top: 5px;">Loan Reference: <strong>{{ $loan->qard_id_string }}</strong></div>
    </div>

    <div class="section">
        <div class="section-title">1. THE PARTIES</div>
        <p>This Loan Agreement is made on {{ now()->format('jS F, Y') }} between:</p>
        <p><strong>LENDER:</strong> Cooperative Multipurpose Society (hereinafter referred to as "the Society").</p>
        <p><strong>BORROWER:</strong> {{ $user->full_name }} (ID: {{ $user->membership_number }}), residing at {{ $user->address ?? '____________________' }}.</p>
    </div>

    <div class="section">
        <div class="section-title">2. LOAN TERMS</div>
        <table>
            <tr>
                <td width="30%">Principal Amount</td>
                <td class="right"><strong>₦ {{ number_format((float) $loan->principal_amount, 2) }}</strong></td>
                <td width="30%">Total Installments</td>
                <td class="right">{{ (int) $loan->total_installments }}</td>
            </tr>
            <tr>
                <td>Admin Fee (Flat/%)</td>
                <td class="right">₦ {{ number_format((float) $loan->admin_fee_flat, 2) }} / {{ $loan->admin_fee_pct }}%</td>
                <td>Repayment Interval</td>
                <td class="right">{{ ucfirst((string) $loan->interval) }}</td>
            </tr>
            <tr>
                <td>Amount Per Installment</td>
                <td class="right"><strong>₦ {{ number_format((float) $loan->per_installment, 2) }}</strong></td>
                <td>Commencement Date</td>
                <td class="right">{{ $loan->approved_at ? $loan->approved_at->format('Y-m-d') : 'TBD' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. UNDERTAKING & COVENANTS</div>
        <p>I, <strong>{{ $user->full_name }}</strong>, hereby acknowledge receipt of the loan amount stated above and agree to the following terms:</p>
        <ol>
            <li>I undertake to repay the loan in full according to the schedule below.</li>
            <li>I authorize the Society to deduct repayments from my linked bank account or wallet.</li>
            <li>In the event of default, I understand that my guarantors will be held liable for the outstanding balance.</li>
            <li>I confirm that the information provided in my loan application is true and correct.</li>
        </ol>
    </div>

    <div class="section" style="page-break-before: auto;">
        <div class="section-title">4. REPAYMENT SCHEDULE</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Due Date</th>
                    <th class="right">Principal (₦)</th>
                    <th class="right">Balance (₦)</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBal = (float) $loan->principal_amount; @endphp
                @foreach($schedule as $row)
                    @php $runningBal -= (float) $row['installment_amount']; @endphp
                    <tr>
                        <td>{{ $row['sequence'] }}</td>
                        <td>{{ $row['due_date'] }}</td>
                        <td class="right">{{ number_format((float) $row['installment_amount'], 2) }}</td>
                        <td class="right">{{ number_format(max($runningBal, 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div class="signature-box">
            <div class="signature-line">BORROWER'S SIGNATURE</div>
            <div>Name: {{ $user->full_name }}</div>
            <div>Date: ____________________</div>
        </div>
        <div class="signature-box" style="float: right;">
            <div class="signature-line">FOR THE SOCIETY (OFFICIAL)</div>
            <div>Name: ____________________</div>
            <div>Date: ____________________</div>
        </div>
    </div>
</body>
</html>
