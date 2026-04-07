<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TakafulPoolEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'direction',
        'amount',
        'reference',
        'meta',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function balance(): float
    {
        $credits = (float) static::where('direction', 'credit')->sum('amount');
        $debits = (float) static::where('direction', 'debit')->sum('amount');
        $bal = $credits - $debits;

        return $bal > 0 ? round($bal, 2) : 0.0;
    }
}
