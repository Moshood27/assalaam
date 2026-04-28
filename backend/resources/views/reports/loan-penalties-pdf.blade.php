<!DOCTYPE html>
<html>
<head>
    <title>Loan Penalties Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Loan Penalties Report</h2>
        <p>Generated on: {{ now()->format('d M Y, h:i A') }}</p>
        @if($branch)
            <p>Branch: {{ $branch->name }}</p>
        @else
            <p>All Branches</p>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Member</th>
                <th>Membership #</th>
                <th>Branch</th>
                <th>Default Started</th>
                <th>Default Cleared</th>
                <th>Default Duration</th>
                <th>Wait Until</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penalties as $penalty)
                <tr>
                    <td>{{ $penalty->user->name }}</td>
                    <td>{{ $penalty->user->membership_number }}</td>
                    <td>{{ $penalty->user->branch?->name ?? 'N/A' }}</td>
                    <td>{{ $penalty->default_started_at?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td>{{ $penalty->default_cleared_at?->format('d/m/Y') ?? 'N/A' }}</td>
                    <td>{{ $penalty->formatted_default_duration }}</td>
                    <td>{{ $penalty->penalty_until?->format('d/m/Y H:i') ?? 'Pending Clear' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Page <span class="pagenum"></span>
    </div>
</body>
</html>
