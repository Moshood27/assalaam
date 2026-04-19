<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8"> <!-- utf-8 works for most cases -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Forcing initial-scale shouldn't be necessary -->
    <meta http-equiv="X-UA-Compatible" content="IE=edge"> <!-- Use the latest (edge) version of IE rendering engine -->
    <meta name="x-apple-disable-message-reformatting">
    <title>Wallet Credited</title>
    <style>
        /* Reset styles */
        html, body { margin: 0 !important; padding: 0 !important; height: 100% !important; width: 100% !important; }
        * { -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt !important; mso-table-rspace: 0pt !important; }
        table { border-spacing: 0 !important; border-collapse: collapse !important; table-layout: fixed !important; margin: 0 auto !important; }
        a { text-decoration: none; color: #0ea5e9; }
        img { -ms-interpolation-mode:bicubic; }
        /* Utility */
        .bg { background: #f8fafc; }
        .card { max-width: 640px; margin: 24px auto; background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e5e7eb; }
        .header { background: #0ea5e9; color: #fff; padding: 22px 24px; font-family: Arial, Helvetica, sans-serif; }
        .brand { font-size: 18px; font-weight: 700; letter-spacing: .3px; }
        .content { padding: 24px; font-family: Arial, Helvetica, sans-serif; color: #111827; line-height: 1.6; font-size: 15px; }
        .salam { font-weight: 600; }
        .amount { font-size: 22px; font-weight: 700; color: #059669; }
        .muted { color: #6b7280; font-size: 13px; }
        .divider { height: 1px; background: #e5e7eb; margin: 16px 0; }
        .btn { display: inline-block; background: #0ea5e9; color: #fff !important; padding: 10px 16px; border-radius: 8px; font-weight: 600; }
        .footer { padding: 16px 24px; text-align: center; font-family: Arial, Helvetica, sans-serif; color: #6b7280; font-size: 12px; }
        .note { background: #f1f5f9; border: 1px dashed #cbd5e1; padding: 12px; border-radius: 8px; font-size: 14px; color: #334155; }
        .meta { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .meta th, .meta td { padding: 10px 12px; text-align: left; font-size: 14px; }
        .meta th { width: 38%; background: #f8fafc; color: #374151; font-weight: 600; }
        .meta td { color: #111827; }
    </style>
</head>
<body class="bg">
    <div class="card">
        <div class="header">
            <div class="brand">{{ $appName ?? config('app.name') }}</div>
        </div>
        <div class="content">
            <p class="salam">Assalāmu ‘alaykum {{ $user->full_name }},</p>
            <p>Alhamdulillāh, your cooperative wallet has just been credited.</p>

            <p class="amount">+ {{ '₦' . number_format($amount, 2) }}</p>

            <table class="meta" role="presentation" cellspacing="0" cellpadding="0">
                <tr>
                    <th>Member</th>
                    <td>{{ $user->full_name }} @if(!empty($user->membership_number)) ({{ $user->membership_number }}) @endif</td>
                </tr>
                <tr>
                    <th>Date & Time</th>
                    <td>{{ optional($timestamp)->timezone(config('app.timezone', 'Africa/Lagos'))?->format('D, d M Y - h:ia') }}</td>
                </tr>
                <tr>
                    <th>Credited Amount</th>
                    <td>{{ '₦' . number_format($amount, 2) }}</td>
                </tr>
                @if(isset($newBalance))
                <tr>
                    <th>New Wallet Balance</th>
                    <td>{{ '₦' . number_format($newBalance, 2) }}</td>
                </tr>
                @endif
                @if(!empty($note))
                <tr>
                    <th>Note</th>
                    <td>{{ $note }}</td>
                </tr>
                @endif
            </table>

            <div class="divider"></div>

            <p>May Allāh put barakah in your wealth and make this a means of ease and goodness for you and your family. Āmīn.</p>
            <p class="muted">If you did not expect this credit, please contact our support immediately.</p>

            @php($supportEmail = config('mail.from.address'))
            <p>
                <a href="mailto:{{ $supportEmail }}" class="btn">Contact Support</a>
            </p>

            <p>Jazākumullāhu khayran,<br>
            {{ $appName ?? config('app.name') }}</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $appName ?? config('app.name') }}. All rights reserved.</p>
            <p class="muted">This is a system notification. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
