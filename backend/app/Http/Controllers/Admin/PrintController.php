<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\User;
use App\Models\UtilityTransaction;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function passbook(Request $request, User $user)
    {
        $year = (int) $request->get('year', now()->year);
        $contributions = $user->contributions()
            ->with('scheme')
            ->whereYear('created_at', $year)
            ->where('status', 'success')
            ->orderBy('created_at', 'asc')
            ->get();

        $branchName = $user->branch?->name;

        $pdf = Pdf::loadView('pdfs.passbook', [
            'user' => $user,
            'year' => $year,
            'contributions' => $contributions,
            'branch' => $branchName,
        ]);

        return $pdf->stream("passbook-{$user->membership_number}-{$year}.pdf");
    }

    public function walletReceipt(Request $request, WalletTransaction $transaction)
    {
        $user = $transaction->user;
        $branchName = $user->branch?->name;

        $pdf = Pdf::loadView('pdfs.wallet_receipt', [
            'user' => $user,
            'tx' => $transaction,
            'branch' => $branchName,
        ]);

        return $pdf->stream("receipt-{$transaction->reference}.pdf");
    }

    public function contributionReceipt(Request $request, Contribution $contribution)
    {
        $user = $contribution->user;
        $branchName = $user->branch?->name;

        // Using wallet_receipt view but adapting for contribution
        // Or I should create a contribution_receipt.blade.php
        // For now let's use a simplified one or the wallet receipt one if it fits.
        // Actually, wallet_receipt expects a $tx with amount, type, reference, source, meta.
        // Contribution has amount, reference, scheme, etc.

        // Create a temporary object that looks like $tx
        $tx = (object) [
            'type' => 'credit',
            'amount' => $contribution->amount,
            'reference' => $contribution->reference,
            'created_at' => $contribution->created_at,
            'source' => 'Manual Contribution ('.($contribution->scheme?->name ?? 'Scheme').')',
            'meta' => [
                'note' => $contribution->status === 'success' ? 'Payment confirmed' : 'Status: '.$contribution->status,
            ],
        ];

        $pdf = Pdf::loadView('pdfs.wallet_receipt', [
            'user' => $user,
            'tx' => $tx,
            'branch' => $branchName,
        ]);

        return $pdf->stream("contribution-receipt-{$contribution->reference}.pdf");
    }

    public function utilityReceipt(Request $request, UtilityTransaction $transaction)
    {
        $user = $transaction->user;
        $branchName = $user->branch?->name;

        // Adapt for utility
        $tx = (object) [
            'type' => 'debit',
            'amount' => $transaction->amount,
            'reference' => $transaction->reference,
            'created_at' => $transaction->created_at,
            'source' => 'Utility: '.ucfirst((string) $transaction->type).' ('.($transaction->network ?? '—').')',
            'meta' => array_merge(
                is_array($transaction->provider_response) ? $transaction->provider_response : [],
                ['note' => 'Phone: '.$transaction->phone_number]
            ),
        ];

        $pdf = Pdf::loadView('pdfs.wallet_receipt', [
            'user' => $user,
            'tx' => $tx,
            'branch' => $branchName,
        ]);

        return $pdf->stream("utility-receipt-{$transaction->reference}.pdf");
    }
}
