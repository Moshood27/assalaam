<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Loan Disbursed (Admin)</title>
    <style>
        body { margin:0; padding:0; background:#f6f8fb; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif; color:#1f2937; }
        .wrapper { width:100%; background:#f6f8fb; padding:24px 0; }
        .container { max-width:700px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
        .header { background:#111827; color:#ffffff; padding:20px 24px; }
        .brand { font-size:18px; font-weight:600; margin:0; }
        .content { padding:24px; font-size:15px; line-height:1.6; }
        .muted { color:#6b7280; font-size:13px; }
        .hr { height:1px; background:#e5e7eb; border:none; margin:20px 0; }
        .table { width:100%; border-collapse:collapse; margin-top:8px; }
        .table th, .table td { padding:8px 0; text-align:left; vertical-align:top; }
        .label { color:#6b7280; width:40%; }
        .value { color:#111827; width:60%; font-weight:600; }
        .footer { padding:16px 24px 24px; color:#6b7280; font-size:12px; text-align:center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <p class="brand">{{ config('app.name') }} • Loan disbursed (Admin)</p>
        </div>
        <div class="content">
            <p>A loan has been disbursed.</p>
            <table class="table" role="presentation">
                <tr>
                    <td class="label">Loan ID</td>
                    <td class="value">{{ $loan->qard_id_string }}</td>
                </tr>
                <tr>
                    <td class="label">Member</td>
                    <td class="value">{{ $loan->user->full_name }} ({{ $loan->user->email }})</td>
                </tr>
                <tr>
                    <td class="label">Principal</td>
                    <td class="value">₦{{ number_format($loan->principal_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Admin fee (flat)</td>
                    <td class="value">₦{{ number_format($loan->admin_fee_flat, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Admin fee (%)</td>
                    <td class="value">{{ number_format($loan->admin_fee_pct, 2) }}%</td>
                </tr>
                <tr>
                    <td class="label">Credited to wallet</td>
                    <td class="value">₦{{ number_format($creditedAmount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value">{{ ucfirst((string) $loan->status) }}</td>
                </tr>
                <tr>
                    <td class="label">Disbursed at</td>
                    <td class="value">{{ $loan->updated_at }}</td>
                </tr>
            </table>

            <hr class="hr" />
            <p class="muted">This notification is for administrators. For full details, review the loan in the dashboard.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Internal notification.</p>
        </div>
    </div>
</div>
</body>
</html>
