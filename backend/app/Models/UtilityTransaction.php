<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UtilityTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'network',
        'phone_number',
        'amount',
        'cost_price',
        'profit',
        'reference',
        'order_id',
        'provider',
        'status',
        'provider_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'provider_response' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
