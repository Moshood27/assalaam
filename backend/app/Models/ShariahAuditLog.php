<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public static function log(?User $user, string $action, array $payload = []): void
    {
        static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'payload' => $payload,
        ]);
    }
}
