<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TakafulPoolEntry;
use App\Models\TakafulContribution;
use App\Services\TakafulService;

class AdminTakafulController extends Controller
{
    public function summary(Request $request)
    {
        $admin = $request->user();
        if (!$admin || !$admin->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $period = $request->query('period') ?: now()->format('Y-m');
        $poolBalance = TakafulPoolEntry::balance();
        $contribs = TakafulContribution::where('period', $period);
        $data = [
            'period' => $period,
            'pool_balance' => $poolBalance,
            'contributions' => [
                'count' => (int) (clone $contribs)->count(),
                'sum' => (float) (clone $contribs)->sum('amount'),
                'by_status' => (clone $contribs)
                    ->selectRaw('status, COUNT(*) as c')
                    ->groupBy('status')
                    ->pluck('c', 'status'),
            ],
        ];

        return response()->json($data);
    }

    public function markDeceased(Request $request, TakafulService $service)
    {
        $admin = $request->user();
        if (!$admin || !$admin->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'date' => 'nullable|date',
        ]);

        /** @var User $user */
        $user = User::findOrFail($validated['user_id']);
        if (!empty($validated['date'])) {
            $user->deceased_at = $validated['date'];
        } else {
            $user->deceased_at = now();
        }
        $user->save();

        $summary = $service->settleMemberLoans($user, 'deceased');

        return response()->json([
            'message' => 'Member marked deceased and loans settlement attempted from Takaful pool',
            'user_id' => $user->id,
            'deceased_at' => $user->deceased_at,
            'settlement' => $summary,
        ]);
    }

    public function markMajorLoss(Request $request, TakafulService $service)
    {
        $admin = $request->user();
        if (!$admin || !$admin->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'date' => 'nullable|date',
        ]);

        /** @var User $user */
        $user = User::findOrFail($validated['user_id']);
        if (!empty($validated['date'])) {
            $user->major_loss_at = $validated['date'];
        } else {
            $user->major_loss_at = now();
        }
        $user->save();

        $summary = $service->settleMemberLoans($user, 'major_loss');

        return response()->json([
            'message' => 'Member marked as suffered major loss and loans settlement attempted from Takaful pool',
            'user_id' => $user->id,
            'major_loss_at' => $user->major_loss_at,
            'settlement' => $summary,
        ]);
    }

    /**
     * Pool ledger with filters and summary for admins.
     */
    public function ledger(Request $request)
    {
        $admin = $request->user();
        if (!$admin || !$admin->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'direction' => 'nullable|in:credit,debit',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'user_id' => 'nullable|integer|min:1',
        ]);
        $perPage = (int) ($validated['per_page'] ?? 15);

        $base = TakafulPoolEntry::query();
        if (!empty($validated['direction'])) {
            $base->where('direction', $validated['direction']);
        }
        if (!empty($validated['date_from'])) {
            $base->whereDate('created_at', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $base->whereDate('created_at', '<=', $validated['date_to']);
        }
        if (!empty($validated['user_id'])) {
            $base->where('meta->user_id', (int) $validated['user_id']);
        }

        $query = (clone $base)->orderByDesc('created_at');
        $paginator = $query->paginate($perPage);
        $result = $paginator->toArray();

        $sumQ = clone $base;
        $credits = (float) (clone $sumQ)->where('direction', 'credit')->sum('amount');
        $debits = (float) (clone $sumQ)->where('direction', 'debit')->sum('amount');
        $result['summary'] = [
            'credits' => round($credits, 2),
            'debits' => round($debits, 2),
            'net' => round($credits - $debits, 2),
            'pool_balance' => TakafulPoolEntry::balance(),
        ];

        return response()->json($result);
    }

    /**
     * Manual batch charge trigger.
     */
    public function charge(Request $request, TakafulService $service)
    {
        $admin = $request->user();
        if (!$admin || !$admin->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'period' => 'nullable|regex:/^\\d{4}-\\d{2}$/',
            'amount' => 'nullable|numeric|min:1',
            'user_id' => 'nullable|integer|exists:users,id',
            'dry_run' => 'nullable|boolean',
        ]);

        $period = $validated['period'] ?? null;
        $amount = array_key_exists('amount', $validated) ? (float) $validated['amount'] : null;
        $userId = array_key_exists('user_id', $validated) ? (int) $validated['user_id'] : null;
        $dryRun = (bool) ($validated['dry_run'] ?? false);

        $result = $service->chargeMonthly($period, $amount, $userId, $dryRun);
        $result['dry_run'] = $dryRun;
        return response()->json($result);
    }
}
