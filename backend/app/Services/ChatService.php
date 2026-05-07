<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ChatService
{
    protected $profanityList = [
        'badword1', 'badword2', // Add more or use a library
    ];

    public function createPrivateRoom(User $user1, User $user2)
    {
        $room = ChatRoom::create([
            'type' => 'private',
        ]);

        $room->members()->create(['user_id' => $user1->id]);
        $room->members()->create(['user_id' => $user2->id]);

        return $room;
    }

    public function sendMessage(ChatRoom $room, User $sender, array $data)
    {
        $body = $data['body'] ?? null;
        $isFlagged = false;

        if ($body) {
            $filteredBody = $this->filterProfanity($body);
            if ($filteredBody !== $body) {
                $isFlagged = true;
                $body = $filteredBody;
            }
        }

        $message = $room->messages()->create([
            'user_id' => $sender->id,
            'type' => $data['type'] ?? 'text',
            'body' => $body,
            'attachment' => $data['attachment'] ?? null,
            'attachment_name' => $data['attachment_name'] ?? null,
            'metadata' => array_merge($data['metadata'] ?? [], ['is_flagged' => $isFlagged]),
        ]);

        $room->update(['last_message_id' => $message->id]);

        return $message;
    }

    public function sendTransactionCard(ChatRoom $room, User $sender, $amount, $purpose, $metadata = [])
    {
        return $this->sendMessage($room, $sender, [
            'type' => 'transaction',
            'body' => "Transaction Request: $purpose ($amount)",
            'metadata' => array_merge([
                'amount' => $amount,
                'purpose' => $purpose,
                'status' => 'pending',
            ], $metadata),
        ]);
    }

    public function sendApprovalRequest(ChatRoom $room, User $sender, $title, $description, $metadata = [])
    {
        return $this->sendMessage($room, $sender, [
            'type' => 'approval',
            'body' => "Approval Required: $title",
            'metadata' => array_merge([
                'title' => $title,
                'description' => $description,
                'status' => 'pending',
            ], $metadata),
        ]);
    }

    public function createGroupRoom(User $creator, $name, $type = 'group', $metadata = [])
    {
        $room = ChatRoom::create([
            'name' => $name,
            'type' => $type,
            'creator_id' => $creator->id,
            'metadata' => array_merge($metadata, ['slug' => Str::slug($name)]),
        ]);

        $room->members()->create(['user_id' => $creator->id, 'role' => 'admin']);

        return $room;
    }

    public function getOrCreateOfficialRoom($name, $roleRequired)
    {
        $room = ChatRoom::where('name', $name)->where('type', 'official')->first();

        if (!$room) {
            // System created room
            $room = ChatRoom::create([
                'name' => $name,
                'type' => 'official',
                'metadata' => [
                    'role_required' => $roleRequired,
                    'is_official' => true,
                    'slug' => Str::slug($name),
                ],
            ]);
        }

        return $room;
    }

    public function assignStaff(ChatRoom $room, User $staff)
    {
        $metadata = $room->metadata ?? [];
        $metadata['assigned_staff_id'] = $staff->id;
        $metadata['assigned_at'] = now();

        $room->update(['metadata' => $metadata]);

        // Ensure staff is a member of the room
        if (!$room->users()->where('user_id', $staff->id)->exists()) {
            $room->members()->create(['user_id' => $staff->id, 'role' => 'staff']);
        }

        return $room;
    }

    public function broadcastMessage(User $sender, $body, $type = 'broadcast', $metadata = [])
    {
        // Broadcast to all "general" or "public" rooms, or create a specific message for all users
        // For simplicity, we'll send it to all active chat rooms of type 'public' or 'group'
        $rooms = ChatRoom::whereIn('type', ['group', 'public'])->get();
        $sentMessages = [];

        foreach ($rooms as $room) {
            $sentMessages[] = $this->sendMessage($room, $sender, [
                'body' => $body,
                'type' => $type,
                'metadata' => array_merge(['is_broadcast' => true], $metadata),
            ]);
        }

        return $sentMessages;
    }

    public function requires2FA(ChatRoom $room)
    {
        $metadata = $room->metadata ?? [];
        return $metadata['requires_2fa'] ?? false;
    }

    public function getChatAnalytics()
    {
        return [
            'total_messages' => ChatMessage::count(),
            'total_rooms' => ChatRoom::count(),
            'active_members' => ChatRoomMember::distinct('user_id')->count(),
            'avg_response_time' => $this->calculateAvgResponseTime(),
        ];
    }

    protected function calculateAvgResponseTime()
    {
        // Logic to calculate average response time between member message and staff reply
        // Placeholder for complex query
        return "15 minutes";
    }

    public function banUser(User $user, $reason = null)
    {
        $metadata = $user->dva_verification_meta ?? []; // Using existing json field if no other available
        $metadata['chat_banned'] = true;
        $metadata['chat_ban_reason'] = $reason;
        $metadata['chat_banned_at'] = now();

        $user->update(['dva_verification_meta' => $metadata]);
        return $user;
    }

    public function unbanUser(User $user)
    {
        $metadata = $user->dva_verification_meta ?? [];
        unset($metadata['chat_banned']);
        unset($metadata['chat_ban_reason']);
        unset($metadata['chat_banned_at']);

        $user->update(['dva_verification_meta' => $metadata]);
        return $user;
    }

    public function isUserBanned(User $user)
    {
        $metadata = $user->dva_verification_meta ?? [];
        return $metadata['chat_banned'] ?? false;
    }

    public function canJoinRoom(ChatRoom $room, User $user)
    {
        $metadata = $room->metadata ?? [];
        $genderRestriction = $metadata['gender_restriction'] ?? null;

        if ($genderRestriction && $user->gender !== $genderRestriction) {
            return false;
        }

        return true;
    }

    public function filterProfanity($text)
    {
        foreach ($this->profanityList as $word) {
            $text = preg_replace("/\b$word\b/i", '***', $text);
        }
        return $text;
    }

    public function getIslamicGreetingSuggestions()
    {
        return [
            'Assalamu Alaikum',
            'Wa Alaikum Assalam',
            'JazakAllah Khair',
            'BarakAllah Feek',
            'InshaAllah',
            'Alhamdulillah',
            'MashaAllah',
        ];
    }

    public function getCannedResponses()
    {
        return [
            'How do I join the Coop?' => 'To join, please click on the "Register" button on the login screen and follow the steps. Assalamu Alaikum.',
            'What are the loan requirements?' => 'Loan eligibility depends on your savings and shares. You can check your eligibility in the Loans section. JazakAllah Khair.',
            'When is the next AGM?' => 'The Annual General Meeting date will be announced via broadcast message. Please stay tuned.',
        ];
    }

    public function expireSensitiveFiles()
    {
        $expiryTime = now()->subHours(48);

        ChatMessage::where('type', 'file')
            ->where('created_at', '<', $expiryTime)
            ->where('metadata->is_sensitive', true)
            ->each(function ($msg) {
                if ($msg->attachment) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($msg->attachment);
                    $msg->update(['attachment' => null, 'body' => '[File expired for privacy]']);
                }
            });
    }

    public function isPrayerTime($latitude, $longitude)
    {
        // Placeholder for prayer time calculation
        // In a real app, use a library like 'ahmed-reza/prayer-times'
        return false;
    }

    public function getAwayMessage()
    {
        $now = now();
        $isFriday = $now->isFriday();
        $hour = $now->hour;

        if ($isFriday && $hour >= 12 && $hour <= 14) {
            return "Our staff is currently observing Jumu'ah prayer and will return shortly. JazakAllah Khair.";
        }

        if ($hour < 8 || $hour > 17) {
            return "Our office is currently closed. We will respond during office hours (8 AM - 5 PM). Assalamu Alaikum.";
        }

        return null;
    }
}
