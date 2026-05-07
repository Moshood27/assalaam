<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoomMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_room_id',
        'user_id',
        'role',
        'joined_at',
        'last_read_at',
        'is_muted',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'last_read_at' => 'datetime',
        'is_muted' => 'boolean',
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
