<?php

namespace App\Observers;

use App\Models\SadaqahProject;
use Illuminate\Support\Facades\Log;

class SadaqahProjectObserver
{
    /**
     * Handle the SadaqahProject "updated" event.
     */
    public function updated(SadaqahProject $sadaqahProject): void
    {
        // Criteria for "Proof of Impact" notification:
        // 1. Project was just deactivated (marked as not active)
        // 2. OR closed_at was just set
        // AND it has media proof uploaded.

        $justDeactivated = $sadaqahProject->wasChanged('active') && !$sadaqahProject->active;
        $justClosed = $sadaqahProject->wasChanged('closed_at') && $sadaqahProject->closed_at && !$sadaqahProject->active;

        if (($justDeactivated || $justClosed) && !empty($sadaqahProject->media_urls)) {
            $this->sendProofOfImpactNotification($sadaqahProject);
        }
    }

    /**
     * Send notifications to all contributors with successful contributions.
     */
    protected function sendProofOfImpactNotification(SadaqahProject $project): void
    {
        // Get all unique users who contributed successfully to this project
        $contributors = $project->contributions()
            ->where('status', 'success')
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->unique('id');

        Log::info("Sending Proof of Impact notifications for Sadaqah project: {$project->name} to {$contributors->count()} contributors.");

        foreach ($contributors as $user) {
            $user->notifyMember(
                "Project Completed: {$project->name}",
                "Your contribution has helped complete this project! Tap to see the physical result of your Sadaqah.",
                [
                    'route' => "/sadaqah/{$project->id}",
                    'project_id' => (string) $project->id,
                    'type' => 'sadaqah_proof_of_impact',
                ],
                ['push', 'database'] // Primarily Push and Database for proof of impact
            );
        }
    }
}
