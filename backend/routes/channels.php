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

// Use Sanctum bearer tokens for channel auth (mobile/web API)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Broadcast::channel('support.{userId}', function ($user, int $userId) {
    // Members can only listen to their own channel; admins can listen to any.
    return (int) $user->id === (int) $userId || (bool) ($user->is_admin ?? false);
});
