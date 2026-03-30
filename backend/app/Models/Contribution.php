<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'scheme_id',
        'project_id',
        'amount',
        'reference',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference)) {
                $model->reference = self::generateReference();
            }
        });
    }

    public static function generateReference(): string
    {
        return 'CNTRB-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }
}
