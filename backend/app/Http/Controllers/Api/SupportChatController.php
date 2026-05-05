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

        $request->validate([
            'body' => [$request->hasFile('attachment') ? 'nullable' : 'required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240'], // 10MB
        ]);

        $type = 'text';
        $attachmentPath = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('support-attachments', 'public');
            $attachmentName = $file->getClientOriginalName();
            $mime = $file->getMimeType();
            $type = str_contains($mime, 'image') ? 'image' : 'file';
        }

        $msg = SupportMessage::create([
            'user_id' => $user->id,
            'sender_type' => 'member',
            'sender_id' => $user->id,
            'body' => trim($request->body) ?: ($type === 'image' ? 'Sent an image' : 'Sent a file'),
            'type' => $type,
            'attachment' => $attachmentPath,
            'attachment_name' => $attachmentName,
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
                'type' => (string) $msg->type,
                'attachment' => $msg->attachment ? asset('storage/' . $msg->attachment) : null,
                'attachment_name' => (string) $msg->attachment_name,
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

        $count = SupportMessage::where('user_id', $user->id)
            ->where('sender_type', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($count > 0) {
            event(new \App\Events\SupportMessagesRead($user->id, 'member'));
        }

        return response()->json(['updated' => $count]);
    }

    /**
     * Broadcast typing status.
     */
    public function typing(Request $request)
    {
        $user = $request->user();
        $isTyping = (bool) $request->input('is_typing', true);

        event(new \App\Events\SupportTyping($user->id, 'member', $isTyping));

        return response()->json(['status' => 'ok']);
    }
}
