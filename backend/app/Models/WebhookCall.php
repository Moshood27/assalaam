<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Builder;

class WebhookCall extends Model
{
    use MassPrunable;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'exception' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        // Prune webhooks older than 30 days
        return static::where('created_at', '<=', now()->subDays(30));
    }
}
