<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Invitation to Join {{ config('app.name') }}</title>
    <style>
        html, body { margin:0 !important; padding:0 !important; height:100% !important; width:100% !important; }
        * { -ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt !important; mso-table-rspace:0pt !important; }
        table { border-spacing:0 !important; border-collapse:collapse !important; table-layout:fixed !important; margin:0 auto !important; }
        a { text-decoration:none; color:#0ea5e9; }
        img { -ms-interpolation-mode:bicubic; }
        .bg { background:#f8fafc; }
        .card { max-width:640px; margin:24px auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; }
        .header { background:#0ea5e9; color:#fff; padding:22px 24px; font-family: Arial, Helvetica, sans-serif; }
        .brand { font-size:18px; font-weight:700; letter-spacing:.3px; }
        .content { padding:24px; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.6; font-size:15px; }
        .salam { font-weight:600; }
        .muted { color:#6b7280; font-size:13px; }
        .divider { height:1px; background:#e5e7eb; margin:16px 0; }
        .btn { display:inline-block; background:#0ea5e9; color:#fff !important; padding:10px 16px; border-radius:8px; font-weight:600; }
        .footer { padding:16px 24px; text-align:center; font-family: Arial, Helvetica, sans-serif; color:#6b7280; font-size:12px; }
    </style>
</head>
<body class="bg">
    @php($supportEmail = config('mail.from.address'))
    <div class="card">
        <div class="header">
            <div class="brand">{{ config('app.name') }}</div>
        </div>
        <div class="content">
            <p class="salam">Assalāmu ‘alaykum {{ $name }},</p>
            <p>You have been invited to join <strong>{{ config('app.name') }}</strong>.</p>

            @if(!empty($customMessage))
                <div class="divider"></div>
                <p><strong>Message from sender:</strong></p>
                <p style="font-style: italic;">"{{ $customMessage }}"</p>
                <div class="divider"></div>
            @endif

            <p>We invite you to register as a member and access our cooperative benefits, including Qard Hasan loans, contributions management, and more.</p>

            <p style="margin:24px 0;">
                <a class="btn" href="{{ $registrationUrl }}" target="_blank" rel="noopener">Complete Registration Form</a>
            </p>

            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>
                <a href="mailto:{{ $supportEmail }}" class="btn">Contact Support</a>
            </p>

            <p>Jazākumullāhu khayran,<br>{{ config('app.name') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="muted">This is a system notification regarding your invitation.</p>
        </div>
    </div>
</body>
</html>
