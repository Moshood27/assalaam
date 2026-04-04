<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>Takaful Summary</title>
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
    <h1>Monthly Takaful Summary ({{ $period }})</h1>

    <p>
        Total members: {{ (int)($count ?? 0) }}<br/>
        Total amount: ₦ {{ number_format((float)($sum ?? 0), 2) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($by_status ?? []) as $status => $c)
                <tr>
                    <td>{{ $status }}</td>
                    <td>{{ $c }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Contributions</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>User ID</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($rows ?? []) as $r)
                <tr>
                    <td>{{ $r->created_at }}</td>
                    <td>{{ $r->user_id }}</td>
                    <td>₦ {{ number_format((float)$r->amount, 2) }}</td>
                    <td>{{ $r->status }}</td>
                    <td>{{ $r->reference }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="small">No contributions</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
