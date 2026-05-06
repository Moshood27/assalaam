<?php

namespace App\Http\Controllers\Api;

use App\Events\SupportMessageSent;
use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Http\Request;

class SupportChatAdminController extends Controller
{
    /**
     * Send a support reply to a member (admin only).
     */
    public function sendToUser(Request $request, User $user)
    {
        $admin = $request->user();
        if (!((bool)($admin->is_admin ?? false))) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'body' => [$request->hasFile('attachment') ? 'nullable' : 'required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
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
            'sender_type' => 'admin',
            'sender_id' => $admin->id,
            'body' => trim($request->body) ?: ($type === 'image' ? 'Sent an image' : 'Sent a file'),
            'type' => $type,
            'attachment' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // Broadcast to the member's private channel
        SupportMessageSent::dispatch($msg);

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
}
