<?php

namespace App\Services;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Scheme;
use App\Models\Contribution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

class ExportService
{
    public function generatePassbookPdf(User $user, int $year): string
    {
        $contributions = $user->contributions()
            ->with('scheme')
            ->where('status', 'success')
            ->when($year > 0, function ($q) use ($year) {
                $q->whereYear('created_at', $year);
            })
            ->orderBy('created_at')
            ->get();

        $startOfYear = Carbon::create($year, 1, 1, 0, 0, 0);
        $yearContributions = $user->contributions()
            ->whereYear('created_at', $year)
            ->where('status', 'success')
            ->get();
        $bfContributions = $user->contributions()
            ->where('created_at', '<', $startOfYear)
            ->where('status', 'success')
            ->get();

        $schemes = Scheme::orderBy('name')->get();
        $matrix = $schemes->map(function ($scheme) use ($yearContributions, $bfContributions) {
            $row = [
                'scheme_name' => $scheme->name,
                'months' => array_fill(1, 12, 0),
                'bf' => 0.0,
                'total' => 0.0,
            ];
            foreach ($bfContributions as $con) {
                if ($con->scheme_id === $scheme->id) {
                    $row['bf'] += (float) $con->amount;
                }
            }
            foreach ($yearContributions as $con) {
                if ($con->scheme_id === $scheme->id) {
                    $month = $con->created_at->month;
                    $row['months'][$month] += (float) $con->amount;
                    $row['total'] += (float) $con->amount;
                }
            }
            return $row;
        });

        $data = [
            'user' => $user,
            'branch' => optional($user->branch)->name,
            'year' => $year,
            'contributions' => $contributions,
            'matrix' => $matrix,
            'grand_total' => $matrix->sum('total'),
            'bf_total' => $matrix->sum('bf'),
        ];

        return Pdf::setOptions(['isHtml5ParserEnabled' => false])
            ->loadView('pdfs.passbook', $data)
            ->output();
    }

    public function generateStatementPdf(User $user, int $months = 6): string
    {
        $startDate = now()->subMonths($months)->startOfDay();

        $openingBalance = (float) WalletTransaction::where('user_id', $user->id)
            ->where('created_at', '<', $startDate)
            ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as balance")
            ->value('balance') ?? 0.0;

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $data = [
            'user' => $user,
            'branch' => optional($user->branch)->name,
            'transactions' => $transactions,
            'opening_balance' => $openingBalance,
            'period' => [
                'from' => $startDate->format('Y-m-d'),
                'to' => now()->format('Y-m-d'),
            ],
        ];

        return Pdf::setOptions(['isHtml5ParserEnabled' => false])
            ->loadView('pdfs.bank_statement', $data)
            ->output();
    }
}
