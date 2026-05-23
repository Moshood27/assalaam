<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Payment Receipt</title>
    <style>
        body { margin:0; padding:0; background:#f6f8fb; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Ubuntu,'Helvetica Neue',Arial,sans-serif; color:#1f2937; }
        .wrapper { width:100%; background:#f6f8fb; padding:24px 0; }
        .container { max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
        .header { background:#0ea5e9; color:#ffffff; padding:20px 24px; }
        .brand { font-size:18px; font-weight:600; margin:0; }
        .content { padding:24px; font-size:15px; line-height:1.6; }
        .cta { display:inline-block; padding:12px 18px; background:#0ea5e9; color:#ffffff !important; text-decoration:none; border-radius:6px; font-weight:600; }
        .muted { color:#6b7280; font-size:13px; }
        .hr { height:1px; background:#e5e7eb; border:none; margin:20px 0; }
        .table { width:100%; border-collapse:collapse; margin-top:8px; }
        .table td { padding:8px 0; vertical-align:top; }
        .label { color:#6b7280; width:45%; }
        .value { color:#111827; width:55%; font-weight:600; }
        .footer { padding:16px 24px 24px; color:#6b7280; font-size:12px; text-align:center; }
    </style>
</head>
<body>
@php($portalUrl = config('app.frontend_url') ?? config('app.url'))
<div class="wrapper">
    <div class="container">
        <div class="header">
            <p class="brand">{{ config('app.name') }} • Payment receipt</p>
        </div>
        <div class="content">
            <p>Assalāmu ‘alaykum {{ $loan->user->full_name }},</p>
            <p>We pray this message finds you in good health and īmān.</p>
            <p>We received your Qard Hasan repayment. May Allāh put barakah in your wealth. Āmīn. Here are the details for your records:</p>
            <table class="table" role="presentation">
                <tr>
                    <td class="label">Loan ID</td>
                    <td class="value">{{ $loan->qard_id_string }}</td>
                </tr>
                <tr>
                    <td class="label">Amount paid</td>
                    <td class="value">₦{{ number_format($repayment->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Reference</td>
                    <td class="value">{{ $repayment->reference }}</td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td class="value">{{ $repayment->paid_at ?: now() }}</td>
                </tr>
                <tr>
                    <td class="label">Remaining principal</td>
                    <td class="value">₦{{ number_format($loan->remaining_principal, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Loan status</td>
                    <td class="value">{{ ucfirst((string) $loan->status) }}</td>
                </tr>
            </table>

            <hr class="hr" />
            <p>You can view the latest schedule and your full repayment history in your member portal.</p>
            @if(!empty($portalUrl))
                <p style="margin:16px 0 0;">
                    <a class="cta" href="{{ $portalUrl }}" target="_blank" rel="noopener">Open Member Portal</a>
                </p>
            @endif

            <p class="muted" style="margin-top:16px;">If you didn’t make this payment, please contact support immediately.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</div>
</body>
</html>
