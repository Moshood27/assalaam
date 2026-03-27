<?php

namespace App\Filament\Resources\AgmSessionResource\Pages;

use App\Filament\Resources\AgmSessionResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateAgmSession extends CreateRecord
{
    protected static string $resource = AgmSessionResource::class;

    protected function afterCreate(): void
    {
        // Announce new AGM/Voting session to all members via Push (best-effort)
        try {
            $push = app(\App\Services\PushService::class);
            $session = $this->record; // AgmSession model
            $title = 'New AGM & Voting Session';
            $body = '"' . ($session->name ?? 'AGM Session') . '" has been created.';

            // Fetch users with device tokens in chunks to avoid memory issues
            User::query()
                ->whereNotNull('device_token')
                ->orWhereNotNull('fcm_token')
                ->chunk(500, function ($users) use ($push, $title, $body, $session) {
                    foreach ($users as $u) {
                        $token = $u->fcm_token ?: ($u->device_token ?? null);
                        if (!$token) continue;
                        $push->send($token, $title, $body, [
                            'type' => 'agm_created',
                            'session_id' => $session->id,
                            'session_name' => (string) $session->name,
                            'status' => (string) $session->status,
                            'start_at' => optional($session->start_at)->toIso8601String(),
                            'end_at' => optional($session->end_at)->toIso8601String(),
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            // ignore push errors
        }
    }
}
