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
Broadcast::routes(['middleware' => ['auth:sanctum,web']]);

Broadcast::channel('support.{userId}', function ($user, int $userId) {
    // Members can only listen to their own channel; admins can listen to any.
    return (int) $user->id === (int) $userId || (bool) ($user->is_admin ?? false);
});

Broadcast::channel('user.{id}', function ($user, int $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    return (bool) $user->is_admin;
});
