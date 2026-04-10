<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Support\Carbon;

class ProjectProposalNotifyVotingOpen extends Command
{
    protected $signature = 'shura:notify-proposal-voting-open';

    protected $description = 'Send push notifications to members when a project proposal voting session becomes open.';

    public function handle(): int
    {
        $now = Carbon::now();

        $proposals = ProjectProposal::query()
            ->whereNull('voting_open_notified_at')
            ->where('status', 'voting')
            ->where(function ($q) use ($now) {
                $q->whereNull('voting_start_at')
                  ->orWhere('voting_start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('voting_end_at')
                  ->orWhere('voting_end_at', '>=', $now);
            })
            ->limit(20)
            ->get();

        if ($proposals->isEmpty()) {
            return self::SUCCESS;
        }

        $push = app(\App\Services\PushService::class);

        foreach ($proposals as $proposal) {
            $title = 'New Project Shura Open';
            $body = 'Voting is now open for the investment proposal: \'' . $proposal->title . '\'.';
            $route = '/shura/proposals/' . $proposal->id;

            User::query()
                ->where(function($q) {
                    $q->whereNotNull('device_token')
                      ->orWhereNotNull('fcm_token');
                })
                ->chunk(500, function ($users) use ($push, $title, $body, $proposal, $route) {
                    foreach ($users as $u) {
                        if (!$u->isEligibleForShura()) continue;

                        $token = $u->fcm_token ?: $u->device_token;
                        if (!$token) continue;

                        $push->send($token, $title, $body, [
                            'type' => 'proposal_voting_open',
                            'proposal_id' => $proposal->id,
                            'title' => (string) $proposal->title,
                            'route' => $route,
                        ]);
                    }
                });

            $proposal->update(['voting_open_notified_at' => now()]);
            $this->info("Notified members for proposal ID {$proposal->id}.");
        }

        return self::SUCCESS;
    }
}
