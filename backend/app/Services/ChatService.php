<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Snipe\BanBuilder\CensorWords;

class ChatService
{
    protected $censor;

    public function __construct()
    {
        if (class_exists('Snipe\BanBuilder\CensorWords')) {
            $this->censor = new \Snipe\BanBuilder\CensorWords();
            $this->censor->setDictionary(['en-us', 'en-uk']);
        }
    }

    public function getOrCreatePrivateRoom(User $user1, User $user2)
    {
        // Try to find an existing private room with exactly these two members
        $room = ChatRoom::where('type', 'private')
            ->whereHas('members', fn($q) => $q->where('user_id', $user1->id))
            ->whereHas('members', fn($q) => $q->where('user_id', $user2->id))
            ->first();

        if ($room) {
            return $room;
        }

        return $this->createPrivateRoom($user1, $user2);
    }

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

        $this->notifyRoomMembers($room, $message, $sender);

        return $message;
    }

    public function notifyRoomMembers(ChatRoom $room, $message, User $sender)
    {
        $roomName = $room->name ?: ($room->type === 'private' ? 'Private Chat' : 'Chat');

        $room->users()
            ->where('users.id', '!=', $sender->id)
            ->get()
            ->each(function (User $user) use ($roomName, $room, $message, $sender) {
                $user->notifyMember(
                    "New message in {$roomName}",
                    "{$sender->name}: " . Str::limit($message->body ?? 'sent an attachment', 50),
                    [
                        'type' => 'chat_message',
                        'room_id' => $room->id,
                        'message_id' => $message->id,
                    ]
                );
            });
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

    public function sendPeerTransfer(ChatRoom $room, User $sender, $amount, $note = '', $metadata = [])
    {
        return $this->sendMessage($room, $sender, [
            'type' => 'peer_transfer',
            'body' => "Sent $amount to you. " . ($note ? "Note: $note" : ""),
            'metadata' => array_merge([
                'amount' => $amount,
                'note' => $note,
                'status' => 'completed',
                'type' => 'transfer_sent'
            ], $metadata),
        ]);
    }

    public function sendPeerRequest(ChatRoom $room, User $sender, $amount, $purpose = '', $metadata = [])
    {
        return $this->sendMessage($room, $sender, [
            'type' => 'peer_request',
            'body' => "Requesting $amount" . ($purpose ? " for $purpose" : ""),
            'metadata' => array_merge([
                'amount' => $amount,
                'purpose' => $purpose,
                'status' => 'pending',
                'type' => 'request_money'
            ], $metadata),
        ]);
    }

    public function sendBillPayment(ChatRoom $room, User $sender, $billType, $amount, $metadata = [])
    {
        return $this->sendMessage($room, $sender, [
            'type' => 'bill_payment',
            'body' => "Bill Payment: $billType ($amount)",
            'metadata' => array_merge([
                'bill_type' => $billType,
                'amount' => $amount,
                'status' => 'completed',
                'paid_at' => now(),
            ], $metadata),
        ]);
    }

    public function sendMudarabahUpdate(ChatRoom $room, User $sender, $projectName, $roi, $amount, $metadata = [])
    {
        return $this->sendMessage($room, $sender, [
            'type' => 'mudarabah_update',
            'body' => "Mudarabah Project Update: $projectName",
            'metadata' => array_merge([
                'project_name' => $projectName,
                'roi' => $roi,
                'amount' => $amount,
                'status' => 'distributed',
                'type' => 'investment_update'
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

        $this->notifyAssignment($room, $staff);

        return $room;
    }

    protected function notifyAssignment(ChatRoom $room, User $staff)
    {
        $room->users()
            ->where('users.id', '!=', $staff->id)
            ->get()
            ->each(function (User $user) use ($room, $staff) {
                $user->notifyMember(
                    "Support Staff Assigned",
                    "{$staff->name} has been assigned to your support inquiry and is ready to help.",
                    [
                        'type' => 'staff_assigned',
                        'room_id' => $room->id,
                        'staff_id' => $staff->id,
                    ]
                );
            });
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
        $supportRooms = ChatRoom::where('type', 'support')->pluck('id');

        $totalMinutes = 0;
        $count = 0;

        foreach ($supportRooms as $roomId) {
            $memberMsg = ChatMessage::where('chat_room_id', $roomId)
                ->whereHas('user', fn($q) => $q->member())
                ->oldest()
                ->first();

            if ($memberMsg) {
                $staffReply = ChatMessage::where('chat_room_id', $roomId)
                    ->where('created_at', '>', $memberMsg->created_at)
                    ->whereHas('user', fn($q) => $q->staff())
                    ->oldest()
                    ->first();

                if ($staffReply) {
                    $totalMinutes += $staffReply->created_at->diffInMinutes($memberMsg->created_at);
                    $count++;
                }
            }
        }

        if ($count === 0) return "N/A";

        $avg = round($totalMinutes / $count);
        return "{$avg} minutes";
    }

    public function banUser(User $user, $reason = null)
    {
        $metadata = $user->dva_verification_meta ?? []; // Using existing json field if no other available
        $metadata['chat_banned'] = true;
        $metadata['chat_ban_reason'] = $reason;
        $metadata['chat_banned_at'] = now();

        $user->virtualAccount()->updateOrCreate([], ['dva_verification_meta' => $metadata]);
        return $user;
    }

    public function unbanUser(User $user)
    {
        $metadata = $user->dva_verification_meta ?? [];
        unset($metadata['chat_banned']);
        unset($metadata['chat_ban_reason']);
        unset($metadata['chat_banned_at']);

        $user->virtualAccount()->updateOrCreate([], ['dva_verification_meta' => $metadata]);
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
        if (!$this->censor) {
            return $text;
        }
        $result = $this->censor->censorString($text);
        return $result['clean'];
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

    public function isPrayerTime($latitude = null, $longitude = null)
    {
        // Nigerian Approximate Prayer Windows (WAT/Africa/Lagos)
        $now = now()->timezone('Africa/Lagos');
        $time = $now->format('Hi'); // e.g., 1330 for 1:30 PM

        $windows = [
            ['0500', '0615'], // Fajr
            ['1300', '1415'], // Dhuhr
            ['1600', '1715'], // Asr
            ['1845', '1945'], // Maghrib
            ['2000', '2115'], // Isha
        ];

        foreach ($windows as $window) {
            if ($time >= $window[0] && $time <= $window[1]) {
                return true;
            }
        }

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
