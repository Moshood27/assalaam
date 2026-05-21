<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShariahAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log(?User $user, string $action, array $payload = []): void
    {
        static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'payload' => $payload,
        ]);

        // Suspicious action detection: Rapid successive approvals
        if ($user && (Str::startsWith($action, 'approve_') || Str::startsWith($action, 'multi_sig_approve_'))) {
            $recentCount = static::where('user_id', $user->id)
                ->where(function ($query) {
                    $query->where('action', 'like', 'approve_%')
                        ->orWhere('action', 'like', 'multi_sig_approve_%');
                })
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            if ($recentCount >= 10) {
                activity('suspicious')
                    ->causedBy($user)
                    ->withProperties([
                        'admin_id' => $user->id,
                        'admin_name' => $user->full_name,
                        'recent_approvals_count' => $recentCount,
                        'timeframe' => '5 minutes',
                    ])
                    ->log("Rapid successive administrative approvals detected");
            }
        }
    }
}
