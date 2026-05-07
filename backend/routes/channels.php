<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Support both Sanctum (mobile/web API) and Web (Filament admin)
// Routes are registered in bootstrap/app.php with proper middleware

Broadcast::channel('support.{userId}', function ($user, int $userId) {
    // Members can only listen to their own channel; admins can listen to any.
    if ((int) $user->id === (int) $userId || $user->isAdmin() || $user->isStaff()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'is_admin' => $user->isAdmin(),
            'is_staff' => $user->isStaff()
        ];
    }
    return false;
});

Broadcast::channel('chat.room.{roomId}', function ($user, int $roomId) {
    // Check if user is a member of the room OR is staff/admin
    if ($user->isAdmin() || $user->isStaff()) {
        return true;
    }

    return \App\Models\ChatRoomMember::where('chat_room_id', $roomId)
        ->where('user_id', $user->id)
        ->exists();
});

Broadcast::channel('App.Models.User.{id}', function ($user, int $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, int $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    return (bool) $user->is_admin;
});
