<?php

namespace App\Http\Controllers\Api;

use App\Events\SupportMessageSent;
use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SupportChatController extends Controller
{
    /**
     * List the authenticated member's support messages (most recent first).
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if ((bool)($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins should view per member.'], 403);
        }

        $perPage = max(10, min(100, (int) $request->integer('per_page', 50)));
        $messages = SupportMessage::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $messages->items(),
            'current_page' => $messages->currentPage(),
            'per_page' => $messages->perPage(),
            'total' => $messages->total(),
            'last_page' => $messages->lastPage(),
        ]);
    }

    /**
     * Send a message from the authenticated member to support.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if ((bool)($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins must use admin endpoints.'], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = SupportMessage::create([
            'user_id' => $user->id,
            'sender_type' => 'member',
            'sender_id' => $user->id,
            'body' => trim($data['body']),
        ]);

        // Broadcast in real-time
        event(new SupportMessageSent($msg));

        // Notify admins about new support message
        \App\Models\User::where('is_admin', true)->each(function ($admin) use ($user, $msg) {
            $admin->notifyMember(
                "New Support Message",
                "Message from {$user->name}: " . \Illuminate\Support\Str::limit($msg->body, 50),
                ['type' => 'support_message', 'user_id' => $user->id]
            );
        });

        return response()->json([
            'message' => 'Sent',
            'data' => [
                'id' => (int) $msg->id,
                'user_id' => (int) $msg->user_id,
                'sender_type' => (string) $msg->sender_type,
                'sender_id' => $msg->sender_id ? (int) $msg->sender_id : null,
                'body' => (string) $msg->body,
                'created_at' => optional($msg->created_at)->toISOString(),
                'read_at' => optional($msg->read_at)->toISOString(),
            ],
        ], 201);
    }

    /**
     * Mark all admin messages as read for this member.
     */
    public function markRead(Request $request)
    {
        $user = $request->user();
        if ((bool)($user->is_admin ?? false)) {
            return response()->json(['message' => 'Admins not applicable.'], 403);
        }

        $count = SupportMessage::where('user_id', $user->id)
            ->where('sender_type', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['updated' => $count]);
    }
}
