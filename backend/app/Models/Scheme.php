<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Scheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_amount',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }
}
