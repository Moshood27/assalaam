<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Loan Amortization Schedule</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 9px; text-transform: uppercase; }
        tfoot td { font-weight: bold; background: #f3f4f6; }
        .right { text-align: right; }
        .badge { display:inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        .badge-success{ background:#d1fae5; color:#065f46; }
        .badge-warn{ background:#fef3c7; color:#92400e; }
        .badge-muted{ background:#e5e7eb; color:#374151; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Loan Schedule</div>
                    <div class="muted">Generated on {{ now()->format('Y-m-d H:i') }}</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->full_name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    <div class="muted">Loan ID: {{ $loan->qard_id_string }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-bottom: 12px">
        <tbody>
            <tr>
                <td>Principal Amount</td>
                <td class="right">â‚¦ {{ number_format((float) $loan->principal_amount, 2) }}</td>
                <td>Total Installments</td>
                <td class="right">{{ (int) $loan->total_installments }}</td>
            </tr>
            <tr>
                <td>Per Installment</td>
                <td class="right">â‚¦ {{ number_format((float) $loan->per_installment, 2) }}</td>
                <td>Interval</td>
                <td class="right">{{ ucfirst((string) $loan->interval) }}</td>
            </tr>
            <tr>
                <td>Total Paid</td>
                <td class="right">â‚¦ {{ number_format((float) $paid_total, 2) }}</td>
                <td>Remaining Principal</td>
                <td class="right">â‚¦ {{ number_format((float) $remaining_principal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:10%">#</th>
                <th style="width:22%">Due Date</th>
                <th class="right" style="width:22%">Installment (â‚¦)</th>
                <th class="right" style="width:22%">Paid (â‚¦)</th>
                <th style="width:24%">Status</th>
            </tr>
        </thead>
        <tbody>
            @php $totalInstallment = 0; $totalPaid = 0; @endphp
            @foreach($schedule as $row)
                @php
                    $inst = (float) ($row['installment_amount'] ?? 0);
                    $paid = (float) ($row['paid_amount'] ?? 0);
                    $totalInstallment += $inst;
                    $totalPaid += $paid;
                    $status = $row['status'] ?? 'pending';
                @endphp
                <tr>
                    <td>{{ $row['sequence'] }}</td>
                    <td>{{ $row['due_date'] }}</td>
                    <td class="right">{{ number_format($inst, 2) }}</td>
                    <td class="right">{{ number_format($paid, 2) }}</td>
                    <td>
                        @if($status === 'paid')
                            <span class="badge badge-success">Paid</span>
                        @elseif($status === 'partial')
                            <span class="badge badge-warn">Partial</span>
                        @else
                            <span class="badge badge-muted">Pending</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Totals</td>
                <td class="right">{{ number_format($totalInstallment, 2) }}</td>
                <td class="right">{{ number_format($totalPaid, 2) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
