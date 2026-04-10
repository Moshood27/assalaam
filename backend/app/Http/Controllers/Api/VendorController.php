<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function profile(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('owner_user_id', $user->id)->first();
        if (!$vendor) {
            return response()->json([
                'vendor' => null,
                'message' => 'No vendor profile found.'
            ]);
        }
        return response()->json($vendor);
    }

    public function upsertProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'settlement_bank_name' => 'nullable|string|max:100',
            'settlement_bank_code' => 'nullable|string|max:20',
            'settlement_account_number' => 'nullable|string|max:30',
            'settlement_account_name' => 'nullable|string|max:150',
        ]);
        $user = $request->user();

        $vendor = Vendor::firstOrNew(['owner_user_id' => $user->id]);
        $vendor->fill(array_merge($validated, [
            'owner_user_id' => $user->id,
        ]));
        // Preserve approval state; default is not approved
        if ($vendor->exists === false) {
            $vendor->is_approved = false;
            $vendor->is_active = true;
        }
        $vendor->save();

        return response()->json([
            'message' => $vendor->wasRecentlyCreated ? 'Vendor profile created' : 'Vendor profile updated',
            'vendor' => $vendor,
        ], $vendor->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Get orders containing products from this vendor.
     */
    public function orders(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('owner_user_id', $user->id)->firstOrFail();

        $orders = StoreOrder::whereHas('items', function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            })
            ->with(['items' => function ($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            }, 'user:id,name,phone'])
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function stats(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('owner_user_id', $user->id)->firstOrFail();

        $totalEarned = StoreOrderItem::where('vendor_id', $vendor->id)
            ->whereNotNull('vendor_paid_at')
            ->sum('vendor_amount');

        $productsQuery = \App\Models\Product::where('vendor_id', $vendor->id);
        $productsCount = (clone $productsQuery)->count();
        $approvedProducts = (clone $productsQuery)->where('is_approved', true)->count();
        $pendingProducts = (clone $productsQuery)->where('is_approved', false)->count();

        $threshold = (int) config('cooperative.low_stock_threshold', 5);
        $lowStockProducts = (clone $productsQuery)
            ->where('track_stock', true)
            ->where('stock_quantity', '<=', $threshold)
            ->count();

        $ordersQuery = StoreOrder::whereHas('items', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        });

        $pendingOrders = (clone $ordersQuery)->whereNotIn('status', ['completed', 'cancelled'])->count();
        $completedOrders = (clone $ordersQuery)->where('status', 'completed')->count();

        // Recent activities: combination of new orders and payouts
        $recentOrders = (clone $ordersQuery)->with('user:id,name')->latest()->take(5)->get()->map(function($o) {
            return [
                'id' => 'ord_' . $o->id,
                'type' => 'order',
                'title' => 'New Order: ' . $o->reference,
                'date' => $o->created_at->diffForHumans(),
                'amount' => (float) $o->total_amount,
                'status' => $o->status
            ];
        });

        $recentPayouts = WalletTransaction::where('user_id', $user->id)
            ->where('source', 'vendor_payout')
            ->where('meta->vendor_id', $vendor->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function($t) {
                return [
                    'id' => 'pay_' . $t->id,
                    'type' => 'payout',
                    'title' => 'Payout: ' . ($t->meta['product_name'] ?? 'Vendor Sale'),
                    'date' => $t->created_at->diffForHumans(),
                    'amount' => (float) $t->amount,
                ];
            });

        $activities = $recentOrders->concat($recentPayouts)->sortByDesc('date')->values()->all();

        // Add current available balance
        $availableBalance = (float) $user->balance;

        return response()->json([
            'total_earned' => (float) $totalEarned,
            'available_balance' => $availableBalance,
            'products_count' => $productsCount,
            'approved_products_count' => $approvedProducts,
            'pending_products_count' => $pendingProducts,
            'low_stock_products_count' => $lowStockProducts,
            'pending_orders_count' => $pendingOrders,
            'completed_orders_count' => $completedOrders,
            'activities' => $activities,
        ]);
    }

    public function settlements(Request $request)
    {
        $user = $request->user();
        $settlements = \App\Models\WithdrawalRequest::where('user_id', $user->id)
            ->where('meta->is_vendor_settlement', true)
            ->latest()
            ->paginate(15);

        return response()->json($settlements);
    }

    public function requestSettlement(Request $request)
    {
        $user = $request->user();
        $vendor = Vendor::where('owner_user_id', $user->id)->firstOrFail();

        if (!$vendor->is_approved) {
            return response()->json(['message' => 'Vendor profile must be approved before requesting settlements.'], 403);
        }

        if (!$vendor->settlement_account_number || !$vendor->settlement_bank_code) {
            return response()->json(['message' => 'Please complete your settlement bank details in your profile.'], 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $amount = (float) $validated['amount'];

        if ($user->balance < $amount) {
            return response()->json(['message' => 'Insufficient balance in your wallet.'], 422);
        }

        $settlement = \App\Models\WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'reference' => 'SETTLE_' . now()->format('YmdHis') . '_' . $user->id,
            'status' => 'pending',
            'bank_code' => $vendor->settlement_bank_code,
            'bank_name' => $vendor->settlement_bank_name,
            'account_number' => $vendor->settlement_account_number,
            'account_name' => $vendor->settlement_account_name,
            'reason' => 'Vendor Settlement',
            'meta' => [
                'is_vendor_settlement' => true,
                'vendor_id' => $vendor->id,
            ]
        ]);

        return response()->json([
            'message' => 'Settlement request submitted successfully.',
            'settlement' => $settlement
        ]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $user = $request->user();
        $vendor = Vendor::where('owner_user_id', $user->id)->firstOrFail();

        $order = StoreOrder::whereHas('items', function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:processing,shipped,delivered,completed,cancelled',
        ]);

        // Specific rules:
        // - Cannot cancel if already completed or shipped? Maybe simple for now.
        // - If transitioning to 'completed', the model listener in StoreOrder will trigger processVendorPayouts()

        $order->status = $validated['status'];
        $order->save();

        return response()->json([
            'message' => 'Order status updated to ' . $validated['status'],
            'order' => $order->load(['items' => function($q) use ($vendor) {
                $q->where('vendor_id', $vendor->id);
            }])
        ]);
    }
}
