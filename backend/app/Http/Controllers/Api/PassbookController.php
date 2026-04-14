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

            return $row;
        });

        return response()->json([
            'year' => $year,
            'matrix' => $matrix,
            'grand_total' => $matrix->sum('total'),
            'bf_total' => $matrix->sum('bf'),
        ]);
    }
}
