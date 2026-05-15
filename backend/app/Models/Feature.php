<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'label', 'description', 'scope', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    public const KNOWN_FEATURES = [
        'withdrawals-enabled' => [
            'label' => 'Withdrawals Enabled',
            'description' => 'Global kill switch for all withdrawals. If disabled, members cannot withdraw funds.',
        ],
        'payment-provider-failover' => [
            'label' => 'Payment Provider Failover',
            'description' => 'Automatically switch from Flutterwave to Paystack (or manual bank transfer) when active.',
        ],
        'maintenance-mode-wallets' => [
            'label' => 'Maintenance Mode (Wallets)',
            'description' => 'Disable all wallet updates (withdrawals, additions) during nightly reconciliation/audit.',
        ],
        'gold-savings-beta' => [
            'label' => 'Gold Savings Beta',
            'description' => 'Enable the new Gold Savings feature for eligible members (Attaqwa Score > 80).',
        ],
        'apply-for-loan' => [
            'label' => 'Apply for Loan',
            'description' => 'Enable loan application interface. Can be restricted to verified members with good scores.',
        ],
        'shura-voting-active' => [
            'label' => 'Shura Voting Active',
            'description' => 'Automatically show the "Vote Now" banner only when an AGM session is live.',
        ],
        'prayer-time-quiet-mode' => [
            'label' => 'Prayer Time Quiet Mode',
            'description' => 'Disables chat notifications or specific "distracting" features during local prayer times.',
        ],
        'gender-segregated-features' => [
            'label' => 'Gender Segregated Features',
            'description' => 'Enable features specifically tailored for women (e.g., Ladies Entrepreneurship Fund).',
        ],
        'show-flw-balance' => [
            'label' => 'Show Flutterwave Balance',
            'description' => 'Only show Flutterwave balance for Admin if compliance status is approved.',
        ],
    ];
}
