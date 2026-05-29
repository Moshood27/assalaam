<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance & Fine Summary - {{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Attendance & Fine Summary Report</div>
        <div>AS-SALAAM Osogbo CICS Ltd</div>
        <div>Year: {{ $year }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Meeting Title</th>
                <th>Date</th>
                <th class="right">Present</th>
                <th class="right">Absent</th>
                <th class="right">Fines Generated (₦)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($meetings as $m)
            <tr>
                <td>{{ $m['meeting_title'] }}</td>
                <td>{{ $m['date'] }}</td>
                <td class="right">{{ $m['present_count'] }}</td>
                <td class="right">{{ $m['absent_count'] }}</td>
                <td class="right">{{ number_format($m['total_fines'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 20px; padding: 10px; background-color: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px;">
        <strong>Note on Fines:</strong> In accordance with Sharia principles and the Cooperative's policy, all attendance fines are considered non-halal income and are strictly moved to the <strong>Sadaqah (Charity) Fund</strong> for public welfare. They are not part of the Cooperative's distributable profit.
    </div>
</body>
</html>
