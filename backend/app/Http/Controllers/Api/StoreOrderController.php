<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreOrderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);
        $user = $request->user();
        $per = $validated['per_page'] ?? 15;
        $q = StoreOrder::with('items')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($per);
        return response()->json($q);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = StoreOrder::with('items')
            ->where('user_id', $user->id)
            ->findOrFail($id);
        return response()->json($order);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|distinct',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
        ]);

        $user = $request->user();

        // Load products and compute totals
        $productIds = collect($validated['items'])->pluck('product_id')->all();
        $products = Product::whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');

        if (count($products) !== count($productIds)) {
            return response()->json(['message' => 'One or more products are invalid/unavailable'], 422);
        }

        $lineItems = [];
        $grandTotal = 0.0;
        $grandCost = 0.0;

        foreach ($validated['items'] as $it) {
            $p = $products[$it['product_id']] ?? null;
            if (!$p) continue;
            $qty = (int) $it['quantity'];
            $unitPrice = (float) $p->selling_price;
            $unitCost = (float) $p->cost_price;
            $lineTotal = round($unitPrice * $qty, 2);
            $lineCost = round($unitCost * $qty, 2);
            $lineProfit = round($lineTotal - $lineCost, 2);
            $grandTotal += $lineTotal;
            $grandCost += $lineCost;
            $lineItems[] = [
                'product_id' => $p->id,
                'product_name' => $p->name,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'line_cost' => $lineCost,
                'line_profit' => $lineProfit,
            ];
        }

        $grandTotal = round($grandTotal, 2);
        $grandCost = round($grandCost, 2);
        $grandProfit = round($grandTotal - $grandCost, 2);

        if ($grandTotal <= 0) {
            return response()->json(['message' => 'Cart total must be greater than zero'], 422);
        }

        if ((float)$user->balance < $grandTotal) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = 'STORE_' . now()->format('YmdHis') . '_' . $user->id . '_' . Str::upper(Str::random(5));

        $order = DB::transaction(function () use ($user, $lineItems, $grandTotal, $grandCost, $grandProfit, $reference) {
            // Deduct wallet first to avoid race conditions
            $user->decrement('balance', $grandTotal);

            $order = StoreOrder::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'total_amount' => $grandTotal,
                'total_cost' => $grandCost,
                'total_profit' => $grandProfit,
                'status' => 'paid',
            ]);

            foreach ($lineItems as $li) {
                StoreOrderItem::create(array_merge($li, [
                    'store_order_id' => $order->id,
                ]));
            }

            // Record wallet debit transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $grandTotal,
                'reference' => $reference,
                'source' => 'store',
                'meta' => [
                    'store_order_id' => $order->id,
                    'items' => collect($lineItems)->map(fn ($li) => [
                        'product_id' => $li['product_id'],
                        'name' => $li['product_name'],
                        'qty' => $li['quantity'],
                        'unit_price' => $li['unit_price'],
                    ])->values()->all(),
                ],
            ]);

            return $order;
        });

        $order->load('items');

        return response()->json([
            'message' => 'Order placed successfully',
            'order' => $order,
            'balance' => (float) $user->fresh()->balance,
        ], 201);
    }
}
