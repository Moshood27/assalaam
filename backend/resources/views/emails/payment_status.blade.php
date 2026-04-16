<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $title ?? 'Payment Update' }}</title>
  <style>
    body { font-family: Arial, Helvetica, sans-serif; color: #111827; }
    .container { max-width: 600px; margin: 0 auto; padding: 16px; }
    .status { font-weight: bold; text-transform: uppercase; }
    .success { color: #065f46; }
    .failed { color: #991b1b; }
    .pending { color: #92400e; }
    .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
    .muted { color: #6b7280; font-size: 12px; }
    a.button { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 10px 16px; border-radius: 6px; text-decoration: none; margin-top: 12px; }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="status {{ $status ?? '' }}">{{ strtoupper($status ?? 'UPDATE') }}</h2>
    <h3 style="margin-top: 0;">{{ $title ?? 'Payment Update' }}</h3>

    <div class="card">
      <p>{{ $messageText ?? '' }}</p>
      @if(!empty($amount))
        <p><strong>Amount:</strong> ₦{{ number_format((float)$amount, 2) }}</p>
      @endif
      @if(!empty($reference))
        <p><strong>Reference:</strong> {{ $reference }}</p>
      @endif
      @if(!empty($channel))
        <p><strong>Channel:</strong> {{ $channel }}</p>
      @endif

      @if(!empty($meta) && is_array($meta))
        <div class="muted">
          <p><strong>Details</strong></p>
          <ul>
          @foreach($meta as $k => $v)
            <li>{{ ucfirst(str_replace('_',' ', (string) $k)) }}: {{ is_scalar($v) ? $v : json_encode($v) }}</li>
          @endforeach
          </ul>
        </div>
      @endif

      @if(!empty($route))
        <p>
          <a href="{{ url($route) }}" class="button">View in App</a>
        </p>
      @endif

      <p class="muted">If you did not initiate this transaction, please contact support immediately.</p>
    </div>

    <p class="muted">This is an automated message from {{ config('app.name') }}. Please do not reply.</p>
  </div>
</body>
</html>
