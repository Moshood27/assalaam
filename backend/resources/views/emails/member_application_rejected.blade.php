<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        html, body { margin:0 !important; padding:0 !important; height:100% !important; width:100% !important; }
        * { -ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt !important; mso-table-rspace:0pt !important; }
        table { border-spacing:0 !important; border-collapse:collapse !important; table-layout:fixed !important; margin:0 auto !important; }
        a { text-decoration:none; color:#ef4444; }
        img { -ms-interpolation-mode:bicubic; }
        .bg { background:#f8fafc; }
        .card { max-width:640px; margin:24px auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; }
        .header { background:#ef4444; color:#fff; padding:22px 24px; font-family: Arial, Helvetica, sans-serif; }
        .brand { font-size:18px; font-weight:700; letter-spacing:.3px; }
        .content { padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6; font-size:15px; }
        .salam { font-weight:600; }
        .muted { color:#6b7280; font-size:13px; }
        .divider { height:1px; background:#e5e7eb; margin:16px 0; }
        .footer { padding:16px 24px; text-align:center; font-family: Arial, Helvetica, sans-serif; color:#6b7280; font-size:12px; }
        .note { background:#fef2f2; border:1px dashed #fecaca; padding:12px; border-radius:8px; font-size:14px; color:#991b1b; }
    </style>
</head>
<body class="bg">
    @php($supportEmail = config('mail.from.address'))
    <div class="card">
        <div class="header">
            <div class="brand">{{ config('app.name') }}</div>
        </div>
        <div class="content">
            <p class="salam">Assalāmu ‘alaykum {{ $application->full_name }},</p>
            <p>Thank you for your interest in joining {{ config('app.name') }}. After reviewing your application, we regret to inform you that we cannot approve it at this time.</p>

            <div class="divider"></div>

            <p class="salam">Reason for rejection:</p>
            <div class="note">
                {{ $reason }}
            </div>

            <p style="margin-top:16px;">If you have any questions or would like to provide more information, you may reply to this email or contact our support team.</p>

            <p>Jazākumullāhu khayran,<br>{{ config('app.name') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="muted">This is a system notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
