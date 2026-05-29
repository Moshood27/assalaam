<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>Takaful Ledger</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f3f4f6; }
        .small { color: #6b7280; font-size: 11px; }
    </style>
</head>
<body>
    <h1>Takaful Pool Ledger</h1>
    <p class="small">
        Filters:
        @if(!empty($filters['direction'])) Direction={{ $filters['direction'] }}; @endif
        @if(!empty($filters['user_id'])) Member ID={{ $filters['user_id'] }}; @endif
        @if(!empty($filters['date_from'])) From={{ $filters['date_from'] }}; @endif
        @if(!empty($filters['date_to'])) To={{ $filters['date_to'] }}; @endif
    </p>

    <p>
        Credits: â‚¦ {{ number_format((float)($summary['credits'] ?? 0), 2) }} &nbsp;|
        Debits: â‚¦ {{ number_format((float)($summary['debits'] ?? 0), 2) }} &nbsp;|
        Pool Balance: â‚¦ {{ number_format((float)($summary['balance'] ?? 0), 2) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Direction</th>
                <th class="text-right">Amount</th>
                <th>Reference</th>
                <th>User ID</th>
                <th>Period</th>
                <th>Qard Code</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r->created_at }}</td>
                    <td>{{ $r->direction }}</td>
                    <td>â‚¦ {{ number_format((float) $r->amount, 2) }}</td>
                    <td>{{ $r->reference }}</td>
                    <td>{{ $r->meta['user_id'] ?? '' }}</td>
                    <td>{{ $r->meta['period'] ?? '' }}</td>
                    <td>{{ $r->meta['qard_code'] ?? '' }}</td>
                    <td>{{ $r->meta['reason'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="small">No records</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
