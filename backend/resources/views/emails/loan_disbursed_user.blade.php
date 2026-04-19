<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Qard Hasan Disbursed</title>
    <style>
        html, body { margin:0 !important; padding:0 !important; height:100% !important; width:100% !important; }
        * { -ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt !important; mso-table-rspace:0pt !important; }
        table { border-spacing:0 !important; border-collapse:collapse !important; table-layout:fixed !important; margin:0 auto !important; }
        a { text-decoration:none; color:#0ea5e9; }
        img { -ms-interpolation-mode:bicubic; }
        .bg { background:#f8fafc; }
        .card { max-width:680px; margin:24px auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; }
        .header { background:#0ea5e9; color:#fff; padding:22px 24px; font-family: Arial, Helvetica, sans-serif; }
        .brand { font-size:18px; font-weight:700; letter-spacing:.3px; }
        .content { padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6; font-size:15px; }
        .salam { font-weight:600; }
        .muted { color:#6b7280; font-size:13px; }
        .divider { height:1px; background:#e5e7eb; margin:16px 0; }
        .btn { display:inline-block; background:#0ea5e9; color:#fff !important; padding:10px 16px; border-radius:8px; font-weight:600; }
        .footer { padding:16px 24px; text-align:center; font-family: Arial, Helvetica, sans-serif; color:#6b7280; font-size:12px; }
        .meta { width:100%; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; }
        .meta th, .meta td { padding:10px 12px; text-align:left; font-size:14px; }
        .meta th { background:#f8fafc; color:#374151; font-weight:600; width:40%; }
        .meta td { color:#111827; }
        .amount { font-size:22px; font-weight:700; color:#059669; }
    </style>
</head>
<body class="bg">
@php($portalUrl = config('app.frontend_url') ?? config('app.url'))
@php($supportEmail = config('mail.from.address'))
<div class="card">
    <div class="header">
        <div class="brand">{{ config('app.name') }}</div>
    </div>
    <div class="content">
        <p class="salam">Assalāmu ‘alaykum {{ $loan->user->full_name }},</p>
        <p>Alhamdulillāh, your Qard Hasan has been disbursed successfully. The amount below has been credited to your cooperative wallet.</p>

        <p class="amount">+ ₦{{ number_format($creditedAmount, 2) }}</p>

        <table class="meta" role="presentation" cellspacing="0" cellpadding="0">
            <tr>
                <th>Loan ID</th>
                <td>{{ $loan->qard_id_string ?: ('QH-' . $loan->id) }}</td>
            </tr>
            <tr>
                <th>Principal Amount</th>
                <td>₦{{ number_format($loan->principal_amount, 2) }}</td>
            </tr>
            <tr>
                <th>Credited to Wallet</th>
                <td>₦{{ number_format($creditedAmount, 2) }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>{{ ucfirst((string) $loan->status) }}</td>
            </tr>
            <tr>
                <th>Disbursed At</th>
                <td>{{ optional($loan->updated_at)->timezone(config('app.timezone','Africa/Lagos'))?->format('D, d M Y - h:ia') }}</td>
            </tr>
            @if(!empty($loan->user?->balance))
            <tr>
                <th>Current Wallet Balance</th>
                <td>₦{{ number_format((float) $loan->user->balance, 2) }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>
        <p>May Allāh make this a means of ease for you and bless your wealth with barakah. Remember, Qard Hasan is a trust—please plan your repayments responsibly. Āmīn.</p>

        <p class="muted">You can view your loan schedule and repayments from your member portal.</p>
        <p>
            @if(!empty($portalUrl))
            <a class="btn" href="{{ $portalUrl }}" target="_blank" rel="noopener">Open Member Portal</a>
            @endif
            <a class="btn" href="mailto:{{ $supportEmail }}">Contact Support</a>
        </p>

        <p>Jazākumullāhu khayran,<br>{{ config('app.name') }}</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p class="muted">This is a system notification. Please do not reply to this email.</p>
    </div>
</div>
</body>
</html>
