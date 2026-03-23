<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QardHasanRepayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'qard_hasan_id',
        'amount',
        'reference',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function qardHasan()
    {
        return $this->belongsTo(QardHasan::class);
    }
}
