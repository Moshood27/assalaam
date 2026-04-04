<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TakafulPoolEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction',
        'amount',
        'reference',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public static function balance(): float
    {
        $credits = (float) static::where('direction', 'credit')->sum('amount');
        $debits = (float) static::where('direction', 'debit')->sum('amount');
        $bal = $credits - $debits;
        return $bal > 0 ? round($bal, 2) : 0.0;
    }
}
