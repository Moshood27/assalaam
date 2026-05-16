<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserAccountUpdated implements ShouldBroadcast, ShouldRescue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $message; // e.g., "Contribution Successful"
    public $action;

    public function __construct(User $user, string $message = "", $action = null)
    {
        $this->user = $user->withoutRelations();
        $this->message = $message;
        $this->action = $action;
    }

    public function broadcastOn(): array
    {
        // Broadcast on a private channel for the specific member
        // AND a private channel for admin global activity feed
        return [
            new PrivateChannel('user.' . $this->user->id),
            new PrivateChannel('admin-notifications'),
        ];
    }

    public function broadcastAs()
    {
        return 'UserAccountUpdated';
    }

    public function broadcastWith(): array
    {
        $eligibility = $this->user->adjustedLoanEligibility();
        $isDefaulter = (bool) $this->user->is_defaulter;

        return [
            'balances' => [
                'wallet' => (float) $this->user->balance,
                'savings' => (float) $this->user->ordinary_savings,
                'gold' => (float) $this->user->gold_balance,
                'special_savings' => (float) $this->user->special_savings_balance,
                'shares' => (float) $this->user->shares_capital,
                'takaful' => (float) ($this->user->takaful_balance ?? 0),
                'outstanding_fines' => (float) $this->user->outstanding_fines,
                'loan_limit' => $isDefaulter ? 0.0 : (float) ($eligibility['eligibility_adjusted'] ?? 0),
                'attaqwa_score' => (int) $this->user->attaqwa_score,
            ],
            'message' => $this->message,
            'action' => $this->action,
            'time' => now()->toDateTimeString(),
        ];
    }
}
