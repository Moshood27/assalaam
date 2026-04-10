<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'content',
        'media_urls',
        'type',
    ];

    protected $casts = [
        'media_urls' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
