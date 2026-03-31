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

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $msg = SupportMessage::create([
            'user_id' => $user->id,
            'sender_type' => 'admin',
            'sender_id' => $admin->id,
            'body' => trim($data['body']),
        ]);

        // Broadcast to the member's private channel
        event(new SupportMessageSent($msg));

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
}
