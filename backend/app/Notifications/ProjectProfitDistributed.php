<?php

namespace App\Notifications;

use App\Models\ProjectProfit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProjectProfitDistributed extends Notification
{
    use Queueable;

    public function __construct(
        public ProjectProfit $profit,
        public float $amount
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_profit_distributed',
            'title' => 'Profit Distributed',
            'message' => 'You received â‚¦'.number_format($this->amount, 2).' from '.$this->profit->project->name,
            'amount' => $this->amount,
            'project_id' => $this->profit->project_id,
            'project_profit_id' => $this->profit->id,
        ];
    }
}
