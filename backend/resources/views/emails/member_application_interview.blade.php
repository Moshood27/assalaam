<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>Interview Invitation - {{ config('app.name') }}</title>
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
        .detail-label { font-weight: bold; color: #4b5563; }
    </style>
</head>
<body class="bg">
    <div class="card">
        <div class="header">
            <div class="brand">{{ config('app.name') }}</div>
        </div>
        <div class="content">
            <p class="salam">Assalāmu ‘alaykum {{ $name }},</p>
            <p>We are pleased to invite you to a meeting regarding your membership application with <strong>{{ config('app.name') }}</strong>.</p>

            <div class="divider"></div>
            <p><strong>Meeting Details:</strong></p>
            <p><span class="detail-label">Type:</span> {{ ucfirst($meetingType) }}</p>
            <p><span class="detail-label">Date & Time:</span> {{ $meetingDateTime }}</p>
            @if($meetingLocationOrLink)
                <p><span class="detail-label">{{ $meetingType === 'online' ? 'Meeting Link' : 'Location' }}:</span>
                    @if($meetingType === 'online')
                        <a href="{{ $meetingLocationOrLink }}">{{ $meetingLocationOrLink }}</a>
                    @else
                        {{ $meetingLocationOrLink }}
                    @endif
                </p>
            @endif

            @if(!empty($customMessage))
                <div class="divider"></div>
                <p><strong>Additional Message:</strong></p>
                <p style="font-style: italic;">"{{ $customMessage }}"</p>
            @endif
            <div class="divider"></div>

            <p>This meeting is part of our approval process to better understand your interests and how we can best serve you within the cooperative.</p>

            <p>If you have any questions or need to reschedule, please contact us.</p>

            <p>Jazākumullāhu khayran,<br>{{ config('app.name') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p class="muted">This is a system notification regarding your application.</p>
        </div>
    </div>
</body>
</html>
