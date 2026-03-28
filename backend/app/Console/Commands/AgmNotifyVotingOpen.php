<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgmSession;
use App\Models\User;
use Illuminate\Support\Carbon;

class AgmNotifyVotingOpen extends Command
{
    protected $signature = 'agm:notify-voting-open';

    protected $description = 'Send push notifications to members when an AGM voting session becomes open (time-window or status).';

    public function handle(): int
    {
        $now = Carbon::now();

        // Pick sessions that are open by status OR within start/end window, and not yet notified
        $sessions = AgmSession::query()
            ->whereNull('voting_open_notified_at')
            ->where(function ($q) use ($now) {
                $q->where('status', 'open')
                  ->orWhere(function ($q2) use ($now) {
                      $q2->where('status', '!=', 'closed')
                         ->whereNotNull('start_at')->whereNotNull('end_at')
                         ->where('start_at', '<=', $now)
                         ->where('end_at', '>=', $now);
                  });
            })
            ->orderByDesc('start_at')
            ->limit(20) // safety cap per run
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('No AGM sessions to notify.');
            return self::SUCCESS;
        }

        $push = app(\App\Services\PushService::class);

        foreach ($sessions as $session) {
            $title = 'AGM Voting Open';
            $body = 'Voting is now open for \'' . ($session->name ?? 'AGM Session') . '\'.';
            $route = '/agm/sessions/' . $session->id;

            // Fan-out to members in chunks
            User::query()
                ->whereNotNull('device_token')
                ->orWhereNotNull('fcm_token')
                ->chunk(500, function ($users) use ($push, $title, $body, $session, $route) {
                    foreach ($users as $u) {
                        $token = $u->fcm_token ?: ($u->device_token ?? null);
                        if (!$token) continue;
                        $push->send($token, $title, $body, [
                            'type' => 'voting_open',
                            'session_id' => $session->id,
                            'session_name' => (string) $session->name,
                            'start_at' => optional($session->start_at)->toIso8601String(),
                            'end_at' => optional($session->end_at)->toIso8601String(),
                            'route' => $route,
                        ]);
                    }
                });

            // Mark as notified to avoid duplicates
            $session->voting_open_notified_at = now();
            $session->save();

            $this->info("Notified members for session ID {$session->id}.");
        }

        return self::SUCCESS;
    }
}
