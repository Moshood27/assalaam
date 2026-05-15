<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\User;
use App\Models\UserVirtualAccount;
use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ProjectInvestment;
use App\Models\SadaqahProject;
use App\Models\SadaqahContribution;
use App\Models\ExpenseEntry;
use App\Models\Setting;
use App\Models\Project;
use App\Mail\RepaymentReceiptUser;
use App\Mail\PaymentStatusMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class WebhookService
{
    public function processPaystack(array $payload)
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];
        $secret = config('services.paystack.secret_key');

        if ($event === 'charge.failed') {
            $this->handlePaystackChargeFailed($data);
            return;
        }

        if ($event === 'charge.success') {
            $this->handlePaystackChargeSuccess($data, $secret);
            return;
        }

        if (in_array($event, ['transfer.success', 'transfer.failed', 'transfer.reversed'])) {
            $this->handlePaystackTransfer($event, $data);
            return;
        }
    }

    protected function handlePaystackChargeFailed(array $data)
    {
        // ... (Logic from handlePaystack in WebhookController)
    }

    protected function handlePaystackChargeSuccess(array $data, string $secret)
    {
        // ... (Logic from handlePaystack in WebhookController)
    }

    protected function handlePaystackTransfer(string $event, array $data)
    {
        // ...
    }
}
