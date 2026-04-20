<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class PayoutService
{
    /**
     * Send money directly to a Nigerian bank account via Paystack Transfers.
     *
     * @param string $accountNumber 10-digit NUBAN account number
     * @param string $bankCode      NUBAN bank code (e.g., 058 for GTBank)
     * @param float $amount         Amount in Naira (will be converted to Kobo)
     * @param string $reference     Unique reference for the transfer
     * @param string $reason        Description of the transfer
     * @return array                Array containing recipient_code and transfer_code
     * @throws Exception            When Paystack returns an error
     */
    public static function sendToBank(string $accountNumber, string $bankCode, float $amount, string $reference, string $reason = 'Payout'): array
    {
        $secretKey = config('services.paystack.secret_key');
        if (empty($secretKey)) {
            throw new Exception('Paystack secret key is not configured.');
        }

        // 1) Create a transfer recipient
        $recipientResponse = Http::withToken($secretKey)
            ->acceptJson()
            ->asJson()
            ->post('https://api.paystack.co/transferrecipient', [
                'type' => 'nuban',
                'name' => 'Expense Payout',
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'currency' => 'NGN',
            ]);

        if (!$recipientResponse->successful()) {
            $message = $recipientResponse->json('message') ?? 'Unknown error creating recipient';
            throw new Exception("Could not create bank recipient: {$message}");
        }

        $recipientCode = $recipientResponse->json('data.recipient_code');
        if (!$recipientCode) {
            throw new Exception('Recipient code was not returned by Paystack.');
        }

        // 2) Initiate the transfer
        $transferResponse = Http::withToken($secretKey)
            ->acceptJson()
            ->asJson()
            ->post('https://api.paystack.co/transfer', [
                'source' => 'balance',
                'amount' => (int) round($amount * 100), // Paystack expects Kobo
                'recipient' => $recipientCode,
                'reason' => $reason,
                'reference' => $reference,
            ]);

        if (!$transferResponse->successful()) {
            $message = $transferResponse->json('message') ?? 'Unknown error initiating transfer';
            throw new Exception("Payout failed: {$message}");
        }

        return [
            'recipient_code' => $recipientCode,
            'transfer_code' => $transferResponse->json('data.transfer_code'),
        ];
    }

    /**
     * Resolve a Nigerian bank account number to its account name.
     */
    public static function resolveAccountNumber(string $accountNumber, string $bankCode): ?string
    {
        $secretKey = config('services.paystack.secret_key');
        if (empty($secretKey)) {
            throw new Exception('Paystack secret key is not configured.');
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get("https://api.paystack.co/bank/resolve", [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
            ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json('data.account_name');
    }

    /**
     * Process an expense payout.
     */
    public static function processExpensePayout(\App\Models\ExpenseEntry $expense): bool
    {
        if ($expense->status !== 'approved') {
            throw new Exception('Only approved expenses can be processed for payout.');
        }

        if (!$expense->account_number || !$expense->bank_code) {
            throw new Exception('Recipient bank account details are missing.');
        }

        $reference = 'EXP-' . $expense->id . '-' . time();
        $reason = $expense->title ?: 'Expense Payout';

        $result = self::sendToBank(
            $expense->account_number,
            $expense->bank_code,
            (float) $expense->amount,
            $reference,
            $reason
        );

        $expense->update([
            'status' => 'processed',
            'payout_reference' => $reference,
            'recipient_code' => $result['recipient_code'],
            'transfer_code' => $result['transfer_code'],
            'processed_at' => now(),
        ]);

        return true;
    }
}
