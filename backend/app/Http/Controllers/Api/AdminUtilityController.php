<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UtilityTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminUtilityController extends Controller
{
    /**
     * List VTU (utility) transactions for admins with filters and optional summary.
     */
    public function transactions(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->is_admin) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|in:airtime,data,electricity,cable',
            'status' => 'nullable|in:pending,success,failed',
            'network' => 'nullable|string|max:50',
            'user_id' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:100', // search phone/reference
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'summary' => 'nullable|boolean',
        ]);

        $perPage = $validated['per_page'] ?? 15;

        if (!Schema::hasTable('utility_transactions')) {
            return response()->json($this->emptyPage($validated['page'] ?? 1, $perPage));
        }

        $base = UtilityTransaction::query()->with(['user:id,name,email,membership_number']);

        if (!empty($validated['type'])) { $base->where('type', $validated['type']); }
        if (!empty($validated['status'])) { $base->where('status', $validated['status']); }
        if (!empty($validated['network'])) {
            $net = strtolower($validated['network']);
            if ($net === 'etisalat') { $net = '9mobile'; }
            $base->where('network', $net);
        }
        if (!empty($validated['user_id'])) { $base->where('user_id', (int)$validated['user_id']); }
        if (!empty($validated['q'])) {
            $q = trim($validated['q']);
            $base->where(function ($w) use ($q) {
                $w->where('phone_number', 'like', "%$q%")
                  ->orWhere('reference', 'like', "%$q%");
            });
        }
        if (!empty($validated['date_from'])) {
            $base->whereDate('created_at', '>=', $validated['date_from']);
        }
        if (!empty($validated['date_to'])) {
            $base->whereDate('created_at', '<=', $validated['date_to']);
        }

        $query = (clone $base)->orderByDesc('created_at');
        $paginator = $query->paginate($perPage);
        $result = $paginator->toArray();

        $withSummary = (bool) ($validated['summary'] ?? true); // default include summary
        if ($withSummary) {
            $sumQ = clone $base;
            $summary = [
                'count' => (int) (clone $sumQ)->count(),
                'amount' => (float) (clone $sumQ)->sum('amount'),
                'cost_price' => (float) (clone $sumQ)->sum('cost_price'),
                'profit' => (float) (clone $sumQ)->sum('profit'),
                'by_status' => (clone $sumQ)
                    ->select('status', DB::raw('COUNT(*) as c'))
                    ->groupBy('status')
                    ->pluck('c', 'status'),
            ];
            $result['summary'] = $summary;
        }

        return response()->json($result);
    }

    private function emptyPage(int $page = 1, int $perPage = 15): array
    {
        $basePath = url('/api/admin/vtu/transactions');
        return [
            'current_page' => $page,
            'data' => [],
            'first_page_url' => $basePath . '?page=1',
            'from' => null,
            'last_page' => 1,
            'last_page_url' => $basePath . '?page=1',
            'links' => [
                ['url' => null, 'label' => '&laquo; Previous', 'active' => false],
                ['url' => $basePath . '?page=1', 'label' => '1', 'active' => true],
                ['url' => null, 'label' => 'Next &raquo;', 'active' => false],
            ],
            'next_page_url' => null,
            'path' => $basePath,
            'per_page' => $perPage,
            'prev_page_url' => null,
            'to' => null,
            'total' => 0,
            'summary' => [
                'count' => 0,
                'amount' => 0,
                'cost_price' => 0,
                'profit' => 0,
                'by_status' => [
                    'success' => 0,
                    'failed' => 0,
                    'pending' => 0,
                ],
            ],
        ];
    }
}
