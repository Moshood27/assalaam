<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Events\ChatMessageUpdated;
use App\Events\ChatMessageDeleted;
use App\Events\ChatTyping;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ChatController extends Controller
{
    use AuthorizesRequests;
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index()
    {
        $user = Auth::user();

        // Auto-create official rooms for system if they don't exist (Admin only triggers this to save resources)
        if ($user->isAdmin()) {
            $this->chatService->getOrCreateOfficialRoom('Board of Directors', 'Board Member');
            $this->chatService->getOrCreateOfficialRoom('Audit Committee', 'Committee Member');
            $this->chatService->getOrCreateOfficialRoom('Investment Committee', 'Committee Member');
        }

        // Rooms where user is a member
        $rooms = $user->chatRooms()->with(['lastMessage', 'users'])->get();

        // Plus official rooms they are eligible for but not yet a member
        if ($user->isBoardMember() || $user->isCommitteeMember()) {
            $officialRooms = ChatRoom::where('type', 'official')
                ->whereNotIn('id', $rooms->pluck('id'))
                ->get()
                ->filter(function ($room) use ($user) {
                    $role = $room->metadata['role_required'] ?? null;
                    if ($role === 'Board Member') return $user->isBoardMember();
                    if ($role === 'Committee Member') return $user->isCommitteeMember();
                    return false;
                });

            $rooms = $rooms->concat($officialRooms);
        }

        return response()->json($rooms);
    }

    public function joinRoom(ChatRoom $room)
    {
        $this->authorize('view', $room);

        if (!$room->members()->where('user_id', Auth::id())->exists()) {
            ChatRoomMember::create([
                'chat_room_id' => $room->id,
                'user_id' => Auth::id(),
                'role' => 'member',
                'joined_at' => now(),
            ]);
        }

        return response()->json(['message' => 'Joined successfully', 'room' => $room->load('users')]);
    }

    public function storeRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:private,group,support',
        ]);

        $room = ChatRoom::create([
            'name' => $request->name,
            'type' => $request->type,
            'creator_id' => Auth::id(),
            'metadata' => [
                'slug' => Str::slug($request->name) . '-' . uniqid(),
                'creator_id' => Auth::id(), // Fallback for no-migration env
            ],
        ]);

        $creator = Auth::user();

        // Authorization: members can only initiate private chats with staff or admins
        if (!$creator->isStaff() && $request->type === 'private') {
            if ($request->has('user_ids')) {
                foreach ($request->user_ids as $uid) {
                    $target = User::find($uid);
                    if ($target && $target->id !== $creator->id && !$target->isStaff()) {
                        $room->delete(); // Clean up
                        abort(403, 'You are only authorized to start private chats with staff or admins.');
                    }
                }
            }
        }

        ChatRoomMember::create([
            'chat_room_id' => $room->id,
            'user_id' => Auth::id(),
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        if ($request->type === 'private') {
            // Find a staff member to join the chat
            $staff = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['staff', 'admin', 'super_admin', 'Branch Manager', 'Clerk']);
            })->first() ?: User::where('is_admin', true)->first();
            if ($staff && $staff->id !== Auth::id()) {
                ChatRoomMember::create([
                    'chat_room_id' => $room->id,
                    'user_id' => $staff->id,
                    'role' => 'member',
                    'joined_at' => now(),
                ]);
            }
        }

        if ($request->type === 'support') {
            Auth::user()->getAuthorizedAdmins()->each(function ($admin) use ($room) {
                $admin->notify(new \App\Notifications\NewSupportInquiryNotification($room, Auth::user()));
            });
        }

        return response()->json($room->load('users'));
    }

    public function show(ChatRoom $room)
    {
        $this->authorize('view', $room);
        $messages = $room->messages()->with('user.badges')->latest()->paginate(50);
        return response()->json([
            'room' => $room->load('users.badges'),
            'messages' => $messages,
        ]);
    }

    public function store(Request $request, ChatRoom $room)
    {
        $this->authorize('view', $room);

        $request->validate([
            'body' => 'nullable|string',
            'type' => 'required|string',
            'attachment' => 'nullable|string',
            'attachment_name' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        $message = $this->chatService->sendMessage($room, Auth::user(), $request->all());

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json($message);
    }

    public function update(Request $request, ChatMessage $message)
    {
        $this->authorize('update', $message);

        $request->validate([
            'body' => 'required|string',
        ]);

        $body = $this->chatService->filterProfanity($request->body);

        $message->update([
            'body' => $body,
            'edited_at' => now(),
        ]);

        broadcast(new ChatMessageUpdated($message))->toOthers();

        return response()->json($message);
    }

    public function destroy(ChatMessage $message)
    {
        $this->authorize('delete', $message);

        $chatRoomId = $message->chat_room_id;
        $messageId = $message->id;

        $message->delete();

        broadcast(new ChatMessageDeleted($messageId, $chatRoomId))->toOthers();

        return response()->json(['status' => 'deleted']);
    }

    public function respond(Request $request, ChatMessage $message)
    {
        $this->authorize('view', $message->room);

        $request->validate([
            'action' => 'required|string', // e.g., 'paid', 'approved', 'declined'
            'metadata' => 'nullable|array',
        ]);

        $metadata = $message->metadata ?? [];
        $metadata['status'] = $request->action;
        $metadata['response_metadata'] = $request->metadata;
        $metadata['responded_at'] = now();
        $metadata['responded_by'] = Auth::id();

        $message->update(['metadata' => $metadata]);

        broadcast(new ChatMessageUpdated($message))->toOthers();

        // Logic for specific actions (e.g., triggering a digital signature log)
        if ($message->type === 'approval' && $request->action === 'approved') {
            // Trigger digital signature log (Ikhlas/Agree)
            activity('chat')
                ->performedOn($message)
                ->causedBy(Auth::user())
                ->withProperties(['action' => 'digital_signature', 'room_id' => $message->chat_room_id])
                ->log('Member accepted agreement (Ikhlas)');
        }

        return response()->json($message);
    }

    public function markRead(Request $request, ChatRoom $room)
    {
        $this->authorize('view', $room);

        $room->messages()
            ->where('user_id', '!=', Auth::id())
            ->whereNull('metadata->read_at')
            ->get()
            ->each(function ($msg) {
                $meta = $msg->metadata ?? [];
                $meta['read_at'] = now();
                $msg->update(['metadata' => $meta]);
            });

        return response()->json(['status' => 'ok']);
    }

    public function typing(Request $request, ChatRoom $room)
    {
        $this->authorize('view', $room);

        $isTyping = (bool) $request->is_typing;
        $user = Auth::user();

        broadcast(new ChatTyping($room->id, $user->id, $user->name, $isTyping))->toOthers();

        return response()->json(['status' => 'ok']);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $user = Auth::user();

        $messages = ChatMessage::whereIn('chat_room_id', $user->chatRooms()->pluck('chat_rooms.id'))
            ->where('body', 'like', "%{$query}%")
            ->with(['room', 'user'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json($messages);
    }

    public function greetings()
    {
        return response()->json($this->chatService->getIslamicGreetingSuggestions());
    }

    public function cannedResponses()
    {
        return response()->json($this->chatService->getCannedResponses());
    }

    public function createPrivateRoom(User $user)
    {
        $creator = Auth::user();

        // Authorization: members can only initiate private chats with staff or admins
        if (!$creator->isStaff() && !$user->isStaff()) {
            abort(403, 'You are only authorized to start private chats with staff or admins.');
        }

        $room = $this->chatService->createPrivateRoom($creator, $user);
        return response()->json($room);
    }

    public function assignStaff(Request $request, ChatRoom $room)
    {
        $this->authorize('update', $room);

        $request->validate([
            'staff_id' => 'required|exists:users,id',
        ]);

        $staff = User::findOrFail($request->staff_id);
        $room = $this->chatService->assignStaff($room, $staff);

        return response()->json($room);
    }

    public function broadcast(Request $request)
    {
        // Only admins can broadcast
        if (!Auth::user()->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'body' => 'required|string',
            'metadata' => 'nullable|array',
        ]);

        $messages = $this->chatService->broadcastMessage(Auth::user(), $request->body, 'broadcast', $request->metadata ?? []);

        foreach ($messages as $message) {
            broadcast(new ChatMessageSent($message))->toOthers();
        }

        return response()->json(['status' => 'broadcasted', 'count' => count($messages)]);
    }

    public function analytics()
    {
        // Only staff/admin can see analytics
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        return response()->json($this->chatService->getChatAnalytics());
    }

    public function ban(Request $request, User $user)
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $request->validate(['reason' => 'nullable|string']);
        $this->chatService->banUser($user, $request->reason);

        return response()->json(['status' => 'banned']);
    }

    public function unban(User $user)
    {
        if (!Auth::user()->isStaff()) {
            abort(403);
        }

        $this->chatService->unbanUser($user);

        return response()->json(['status' => 'unbanned']);
    }

    public function status()
    {
        return response()->json([
            'away_message' => $this->chatService->getAwayMessage(),
            'is_prayer_time' => $this->chatService->isPrayerTime(null, null),
        ]);
    }
}
