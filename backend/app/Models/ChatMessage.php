<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'type',
        'body',
        'attachment',
        'attachment_name',
        'metadata',
        'edited_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'edited_at' => 'datetime',
    ];

    public function room()
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
