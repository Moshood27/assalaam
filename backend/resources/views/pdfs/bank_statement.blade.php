<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bank Statement</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 20px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
        .summary { margin-top: 20px; width: 300px; float: right; }
        .summary table { border: none; }
        .summary td { border: none; padding: 4px 0; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .credit { color: #059669; }
        .debit { color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Bank Statement</div>
                    <div class="muted">Period: {{ $period['from'] }} to {{ $period['to'] }}</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->full_name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @if(!empty($branch))
                        <div class="muted">Branch: {{ $branch }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 15%">Date</th>
                <th>Description</th>
                <th style="width: 15%">Reference</th>
                <th class="right" style="width: 12%">Credit (â‚¦)</th>
                <th class="right" style="width: 12%">Debit (â‚¦)</th>
                <th class="right" style="width: 15%">Balance (â‚¦)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $period['from'] }}</td>
                <td colspan="4"><strong>OPENING BALANCE</strong></td>
                <td class="right"><strong>{{ number_format($opening_balance, 2) }}</strong></td>
            </tr>
            @php $currentBalance = $opening_balance; @endphp
            @foreach($transactions as $tx)
                @php
                    $isCredit = strtolower((string)$tx->type) === 'credit';
                    $amt = (float) $tx->amount;
                    $currentBalance += ($isCredit ? $amt : -$amt);

                    $desc = ucwords(str_replace('_', ' ', (string)$tx->source));
                    $meta = is_array($tx->meta) ? $tx->meta : json_decode((string)$tx->meta, true);

                    if ($tx->source === 'p2p_transfer') {
                        if ($isCredit && isset($meta['from_name'])) {
                            $desc .= " from " . $meta['from_name'];
                        } elseif (!$isCredit && isset($meta['to_name'])) {
                            $desc .= " to " . $meta['to_name'];
                        }
                    }

                    if (!empty($meta['maintenance_charge'])) {
                        $desc .= " (Net of â‚¦" . number_format((float)$meta['maintenance_charge'], 2) . " fee)";
                    }
                @endphp
                <tr>
                    <td>{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $desc }}</td>
                    <td class="muted">{{ $tx->reference }}</td>
                    <td class="right credit">{{ $isCredit ? number_format($amt, 2) : '' }}</td>
                    <td class="right debit">{{ !$isCredit ? number_format($amt, 2) : '' }}</td>
                    <td class="right">{{ number_format($currentBalance, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="clearfix">
        <div class="summary">
            <table>
                <tr>
                    <td><strong>Closing Balance</strong></td>
                    <td class="right"><strong>â‚¦ {{ number_format($currentBalance, 2) }}</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <p class="muted" style="margin-top:40px; border-top: 1px solid #e5e7eb; padding-top: 10px;">
        Generated on {{ now()->format('Y-m-d H:i:s') }}<br>
        This is a computer generated statement and does not require a signature.
    </p>
</body>
</html>
