<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Member List</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; word-wrap: break-word; }
        th { background-color: #f2f2f2; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #047857; }
        .footer { position: fixed; bottom: -30px; width: 100%; text-align: center; font-size: 8px; color: #777; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }} - Member List</h1>
        @if(isset($branchName))
            <h2>Branch: {{ $branchName }}</h2>
        @endif
        <p>Generated on: {{ date('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">S/N</th>
                <th style="width: 15%;">Member #</th>
                <th style="width: 25%;">Name</th>
                <th style="width: 15%;">Phone</th>
                <th style="width: 15%;">Branch</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 12%;">Balance</th>
                <th style="width: 12%;">Gold Bal (g)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $index => $user)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $user->membership_number }}</td>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ $user->phone }}</td>
                    <td>{{ $user->branch?->name ?? 'N/A' }}</td>
                    <td>{{ $user->deceased_at ? 'Deceased' : 'Active' }}</td>
                    <td>₦{{ number_format((float)($user->balance ?? 0), 2) }}</td>
                    <td>{{ number_format((float)($user->gold_balance ?? 0), 4) }}g</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
    </div>
</body>
</html>
