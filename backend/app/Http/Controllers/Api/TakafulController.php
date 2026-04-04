<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TakafulContribution;
use App\Models\TakafulPoolEntry;
use App\Services\TakafulService;

class TakafulController extends Controller
{
    /**
     * Return summary for the authenticated member.
     */
    public function summary(Request $request, TakafulService $service)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $period = $request->query('period') ?: now()->format('Y-m');
        $monthlyAmount = $service->monthlyAmount();

        $paidThisPeriod = TakafulContribution::where('user_id', $user->id)
            ->where('period', $period)
            ->where('status', 'success')
            ->exists();

        $myTotal = (float) TakafulContribution::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('amount');

        $poolBalance = TakafulPoolEntry::balance();

        return response()->json([
            'period' => $period,
            'monthly_amount' => $monthlyAmount,
            'paid_this_period' => $paidThisPeriod,
            'my_total_contributions' => round($myTotal, 2),
            'pool_balance' => $poolBalance,
        ]);
    }

    /**
     * Allow member to pay the current month's Takaful contribution manually (or for a specified period).
     */
    public function payNow(Request $request, TakafulService $service)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'period' => 'nullable|regex:/^\\d{4}-\\d{2}$/',
            'amount' => 'nullable|numeric|min:1',
        ]);
        $period = $validated['period'] ?? null;
        $amount = array_key_exists('amount', $validated) ? (float) $validated['amount'] : null;

        $result = $service->payNow($user, $period, $amount);
        if ($result['status'] === 'insufficient_funds') {
            return response()->json(['message' => 'Insufficient wallet balance', 'result' => $result], 422);
        }
        if ($result['status'] === 'already_paid') {
            return response()->json(['message' => 'Already paid for this period', 'result' => $result], 409);
        }
        return response()->json($result);
    }

    /**
     * Paginated list of the member's contributions
     */
    public function contributions(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = TakafulContribution::where('user_id', $user->id)
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage);
        return response()->json($paginator);
    }
}
