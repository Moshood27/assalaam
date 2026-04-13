<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail Report - {{ $date }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">System Audit Trail Report</div>
        <div>At-Taqwa Osogbo CICS Ltd</div>
        <div>Last {{ $days }} days - Generated: {{ $date }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Causer</th>
                <th>Subject</th>
                <th>Action</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $a)
            <tr>
                <td>{{ $a->created_at->toDateTimeString() }}</td>
                <td>{{ $a->causer?->name ?? 'System' }}</td>
                <td>{{ $a->subject_type }} ({{ $a->subject_id }})</td>
                <td>{{ $a->description }}</td>
                <td>
                    @if(!empty($a->properties))
                        <pre style="font-size: 8px;">{{ json_encode($a->properties, JSON_PRETTY_PRINT) }}</pre>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
