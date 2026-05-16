<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'label', 'description', 'scope', 'value'];

    protected $appends = ['display_name', 'display_scope'];

    public function getDisplayNameAttribute()
    {
        if ($this->label) return $this->label;
        return self::KNOWN_FEATURES[$this->name]['label'] ?? $this->name;
    }

    public function getDisplayScopeAttribute()
    {
        if ($this->scope === 'global') return 'Global';
        if (str_contains((string)$this->scope, '|')) {
            [$class, $id] = explode('|', (string)$this->scope);
            $shortClass = class_basename($class);
            return "{$shortClass} #{$id}";
        }
        return $this->scope;
    }

    public function getValueAttribute($value)
    {
        if ($value === null) return null;

        try {
            $unserialized = @unserialize($value);
            if ($unserialized !== false || $value === serialize(false)) {
                return $unserialized;
            }
        } catch (\Throwable $e) {
            // Fallback to JSON if serialization fails
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = serialize($value);
    }

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
            'description' => 'Enable the new Gold Savings feature. When active, it is available to all members by default.',
        ],
        'apply-for-loan' => [
            'label' => 'Apply for Loan',
            'description' => 'Enable loan application interface for all members by default.',
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
