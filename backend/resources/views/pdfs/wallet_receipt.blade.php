<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Wallet Transaction Receipt</title>
    <style>
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #111827; font-size: 11px; }
        .header { display:block; margin-bottom: 20px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 10px; }
        .badge { display:inline-block; padding: 2px 6px; border-radius: 4px; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        .badge-credit { background: #d1fae5; color: #065f46; }
        .badge-debit { background: #fee2e2; color: #991b1b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        th { background: #111827; color: #fff; font-size: 9px; text-transform: uppercase; }
        .right { text-align: right; }
        .section { margin-top: 12px; }
        .footer { margin-top: 20px; font-size: 10px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="border:none; width: 100%;">
            <tr style="border:none;">
                <td style="border:none; width: 50%; vertical-align: top;">
                    <div class="title">Transaction Receipt</div>
                    <div class="muted">Proof of payment/transfer.</div>
                </td>
                <td style="border:none; width: 50%; text-align: right; vertical-align: top;">
                    <div><strong>{{ $user->name }}</strong></div>
                    <div class="muted">Membership ID: {{ $user->membership_number }}</div>
                    @if(!empty($branch))
                        <div class="muted">Branch: {{ $branch }}</div>
                    @endif
                    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        @php($isCredit = strtolower((string)($tx->type ?? '')) === 'credit')
        <div>
            <span class="badge {{ $isCredit ? 'badge-credit' : 'badge-debit' }}">{{ strtoupper($tx->type ?? '-') }}</span>
        </div>
        <table>
            <tbody>
                @php($m = is_array($tx->meta) ? $tx->meta : json_decode((string)$tx->meta, true))
                @if(is_array($m) && isset($m['maintenance_charge']))
                <tr>
                    <td style="width: 40%">Gross Amount</td>
                    <td class="right">₦ {{ number_format((float)($m['gross_amount'] ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Maintenance Charge</td>
                    <td class="right" style="color: #991b1b;">- ₦ {{ number_format((float)($m['maintenance_charge'] ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td><strong>Net Credited Amount</strong></td>
                    <td class="right"><strong>₦ {{ number_format((float)($tx->amount ?? 0), 2) }}</strong></td>
                </tr>
                @else
                <tr>
                    <td style="width: 40%">Amount</td>
                    <td class="right">₦ {{ number_format((float)($tx->amount ?? 0), 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td>Reference</td>
                    <td>{{ $tx->reference ?? ('TX' . $tx->id) }}</td>
                </tr>
                <tr>
                    <td>Date & Time</td>
                    <td>{{ optional($tx->created_at)->format('Y-m-d H:i') }}</td>
                </tr>
                <tr>
                    <td>Channel / Source</td>
                    <td>
                        @php($src = (string)($tx->source ?? ''))
                        @if($src === 'paystack_dva') Bank Transfer (DVA)
                        @elseif($src === 'paystack_charge') Card Payment
                        @elseif($src === 'paystack_autosave') Smart Savings (Autosave)
                        @elseif($src === 'wallet_allocation') Allocation to Schemes
                        @elseif($src === 'p2p_transfer') Member P2P Transfer
                        @elseif(str_starts_with($src, 'vtu_')) Value-added Services (VTU)
                        @else {{ $src ?: '—' }}
                        @endif
                    </td>
                </tr>
                @if(!empty($tx->meta))
                    <tr>
                        <td>Notes</td>
                        <td>
                            @php($m = is_array($tx->meta) ? $tx->meta : json_decode((string)$tx->meta, true))
                            @if(is_array($m))
                                @if(isset($m['note'])) {{ $m['note'] }} @endif
                                @if(isset($m['to_name'])) To: {{ $m['to_name'] }} @endif
                                @if(isset($m['from_name'])) From: {{ $m['from_name'] }} @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <p class="footer">For support, contact the cooperative office with this reference: <strong>{{ $tx->reference ?? ('TX' . $tx->id) }}</strong>.</p>
</body>
</html>
