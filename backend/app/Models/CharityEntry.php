<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class CharityEntry extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'amount', 'source', 'description', 'reference'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
