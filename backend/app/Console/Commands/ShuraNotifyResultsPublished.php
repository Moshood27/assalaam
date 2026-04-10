<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AgmSession;
use App\Models\ProjectProposal;
use App\Models\User;

class ShuraNotifyResultsPublished extends Command
{
    protected $signature = 'shura:notify-results-published';

    protected $description = 'Send push notifications when AGM or Proposal results are published.';

    public function handle(): int
    {
        $push = app(\App\Services\PushService::class);

        // AGM Results
        $agms = AgmSession::where('status', 'closed')
            ->whereNull('results_notified_at')
            ->get();

        foreach ($agms as $agm) {
            $this->notifyUsers(
                $push,
                'AGM Results Published',
                "The results for '{$agm->name}' are now available.",
                '/agm/sessions/' . $agm->id,
                ['type' => 'agm_results', 'id' => $agm->id]
            );
            $agm->update(['results_notified_at' => now()]);
        }

        // Proposal Results
        $proposals = ProjectProposal::where('status', 'closed')
            ->whereNull('results_notified_at')
            ->get();

        foreach ($proposals as $proposal) {
            $this->notifyUsers(
                $push,
                'Proposal Results Published',
                "The results for the proposal '{$proposal->title}' are now available.",
                '/shura/proposals/' . $proposal->id,
                ['type' => 'proposal_results', 'id' => $proposal->id]
            );
            $proposal->update(['results_notified_at' => now()]);
        }

        return self::SUCCESS;
    }

    protected function notifyUsers($push, $title, $body, $route, $data)
    {
        User::query()
            ->where(function($q) {
                $q->whereNotNull('device_token')
                  ->orWhereNotNull('fcm_token');
            })
            ->chunk(500, function ($users) use ($push, $title, $body, $route, $data) {
                foreach ($users as $u) {
                    $token = $u->fcm_token ?: $u->device_token;
                    if (!$token) continue;

                    $push->send($token, $title, $body, array_merge($data, ['route' => $route]));
                }
            });
    }
}
