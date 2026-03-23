<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharityEntry extends Model
{
    use HasFactory;

    protected $table = 'charity_ledger';

    protected $fillable = [
        'user_id',
        'source',
        'amount',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
