<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'label', 'description', 'scope', 'value'];

    protected $casts = [
        'value' => 'json',
    ];
}
