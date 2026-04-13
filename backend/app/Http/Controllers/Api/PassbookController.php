<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PassbookController extends Controller
{
    public function getMatrix(Request $request, int $year)
    {
        $user = $request->user();

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

            $row['total'] += $row['bf']; // Include BF in total

            return $row;
        });

        // Combine Savings and Shares into "Passbook" for the member-facing view
        $savingsRowIdx = $matrix->search(fn($r) => $r['scheme_name'] === 'Savings');
        $sharesRowIdx = $matrix->search(fn($r) => $r['scheme_name'] === 'Shares');

        if ($savingsRowIdx !== false && $sharesRowIdx !== false) {
            $savings = $matrix[$savingsRowIdx];
            $shares = $matrix[$sharesRowIdx];

            $passbookRow = [
                'scheme_name' => 'Passbook (Savings + Shares)',
                'bf' => $savings['bf'] + $shares['bf'],
                'months' => array_fill(1, 12, 0),
                'total' => $savings['total'] + $shares['total'],
            ];
            for ($m = 1; $m <= 12; $m++) {
                $passbookRow['months'][$m] = $savings['months'][$m] + $shares['months'][$m];
            }

            // Remove originals and add combined at the top
            $matrix->forget($savingsRowIdx);
            $matrix->forget($sharesRowIdx);
            $matrix = collect([$passbookRow])->concat($matrix->values());
        }

        return response()->json([
            'year' => $year,
            'matrix' => $matrix,
            'grand_total' => $matrix->sum('total'),
        ]);
    }
}
