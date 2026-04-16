<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\User;
use App\Models\UtilityTransaction;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PrintController extends Controller
{
    public function passbook(Request $request, User $user)
    {
        $year = (int) $request->get('year', now()->year);
        $startOfYear = Carbon::create($year, 1, 1, 0, 0, 0);

        $contributions = $user->contributions()
            ->with('scheme')
            ->whereYear('created_at', $year)
            ->where('status', 'success')
            ->orderBy('created_at', 'asc')
            ->get();

        $bfContributions = $user->contributions()
            ->where('created_at', '<', $startOfYear)
            ->where('status', 'success')
            ->get();

        $schemes = Scheme::orderBy('name')->get();

        $matrix = $schemes->map(function ($scheme) use ($contributions, $bfContributions) {
            $row = [
                'scheme_name' => $scheme->name,
                'months' => array_fill(1, 12, 0.0),
                'bf' => 0.0,
                'total' => 0.0,
            ];

            foreach ($bfContributions as $con) {
                if ($con->scheme_id === $scheme->id) {
                    $row['bf'] += (float) $con->amount;
                }
            }

            foreach ($contributions as $con) {
                if ($con->scheme_id === $scheme->id) {
                    $month = $con->created_at->month;
                    $row['months'][$month] += (float) $con->amount;
                    $row['total'] += (float) $con->amount;
                }
            }

            return $row;
        });

        $branchName = $user->branch?->name;

        $pdf = Pdf::loadView('pdfs.passbook', [
            'user' => $user,
            'year' => $year,
            'contributions' => $contributions,
            'branch' => $branchName,
            'matrix' => $matrix,
            'grand_total' => $matrix->sum('total'),
            'bf_total' => $matrix->sum('bf'),
        ]);

        return $pdf->stream("passbook-{$user->membership_number}-{$year}.pdf");
    }

    public function usersList(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        ini_set('pcre.backtrack_limit', '5000000');

        $query = User::query()->with('branch');

        if ($branchId = $request->get('branch_id')) {
            if (is_array($branchId)) {
                $query->whereIn('branch_id', $branchId);
            } else {
                $query->where('branch_id', $branchId);
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('membership_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')
            ->select(['id', 'name', 'surname', 'other_names', 'membership_number', 'phone', 'branch_id', 'deceased_at', 'balance', 'gold_balance'])
            ->get();

        $branchName = null;
        if ($branchId) {
            if (is_array($branchId)) {
                $branchName = Branch::whereIn('id', $branchId)->pluck('name')->implode(', ');
            } else {
                $branchName = Branch::find($branchId)?->name;
            }
        }

        $pdf = Pdf::loadView('pdfs.member_list', [
            'users' => $users,
            'branchName' => $branchName,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream("member-list.pdf");
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
