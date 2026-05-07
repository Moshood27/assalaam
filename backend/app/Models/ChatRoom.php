<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'avatar',
        'creator_id',
        'metadata',
        'last_message_id',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function members()
    {
        return $this->hasMany(ChatRoomMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_room_members', 'chat_room_id', 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function lastMessage()
    {
        return $this->belongsTo(ChatMessage::class, 'last_message_id');
    }
}
