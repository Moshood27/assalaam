<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>New member created</title>
    <style>
        body { margin:0; padding:0; background:#f6f8fb; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', Arial, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', sans-serif; color:#1f2937; }
        .wrapper { width:100%; background:#f6f8fb; padding:24px 0; }
        .container { max-width:640px; margin:0 auto; background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 2px 6px rgba(0,0,0,0.05); }
        .header { background:#111827; color:#ffffff; padding:20px 24px; }
        .brand { font-size:18px; font-weight:600; margin:0; }
        .content { padding:24px; font-size:15px; line-height:1.6; }
        .table { width:100%; border-collapse:collapse; margin-top:8px; }
        .table td { padding:8px 0; vertical-align:top; }
        .label { color:#6b7280; width:40%; }
        .value { color:#111827; width:60%; font-weight:600; }
        .hr { height:1px; background:#e5e7eb; border:none; margin:20px 0; }
        .footer { padding:16px 24px 24px; color:#6b7280; font-size:12px; text-align:center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <div class="header">
            <p class="brand">{{ config('app.name') }} • New member created</p>
        </div>
        <div class="content">
            <p>A new member account has been created.</p>
            <table class="table" role="presentation">
                <tr>
                    <td class="label">Name</td>
                    <td class="value">{{ $user->full_name }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td class="value">{{ $user->email ?: '—' }}</td>
                </tr>
                @if($user->branch)
                    <tr>
                        <td class="label">Branch</td>
                        <td class="value">{{ $user->branch->name }}</td>
                    </tr>
                @endif
                @if(!empty($user->membership_number))
                    <tr>
                        <td class="label">Membership #</td>
                        <td class="value">{{ $user->membership_number }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Created at</td>
                    <td class="value">{{ $user->created_at }}</td>
                </tr>
            </table>

            <hr class="hr" />
            <p style="color:#6b7280; font-size:13px;">This notification was sent to administrators. No action is required unless follow-up is needed.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Internal notification.</p>
        </div>
    </div>
</div>
</body>
</html>
