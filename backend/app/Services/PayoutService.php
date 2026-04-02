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
     * @return bool                 True if the transfer request was accepted
     * @throws Exception            When Paystack returns an error
     */
    public static function sendToBank(string $accountNumber, string $bankCode, float $amount, string $reference): bool
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
                'name' => 'Member Payout',
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
                'reason' => 'Loan Disbursement: ' . $reference,
                'reference' => $reference,
            ]);

        if (!$transferResponse->successful()) {
            $message = $transferResponse->json('message') ?? 'Unknown error initiating transfer';
            throw new Exception("Payout failed: {$message}");
        }

        return true;
    }
}
