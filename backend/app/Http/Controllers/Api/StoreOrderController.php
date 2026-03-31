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
            'note' => 'nullable|string|max:500',
            'pin' => ['required','regex:/^\d{4}$/'],
            'financing' => 'nullable|array',
            'financing.enabled' => 'nullable|boolean',
            'financing.months' => 'required_if:financing.enabled,true|integer|min:6|max:12',
            'financing.profit_rate' => 'required_if:financing.enabled,true|numeric|min:0.1|max:0.15',
        ]);

        $user = $request->user();

        // Enforce Transaction PIN for wallet debit
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        $note = trim((string) ($validated['note'] ?? ''));

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

        $reference = 'STORE_' . now()->format('YmdHis') . '_' . $user->id . '_' . Str::upper(Str::random(5));

        $fin = $validated['financing'] ?? null;
        $isFinancing = is_array($fin) && !empty($fin['enabled']);

        if ($isFinancing) {
            $months = (int) ($fin['months'] ?? 0);
            $profitRate = (float) ($fin['profit_rate'] ?? 0); // e.g. 0.10 => 10%

            // Compute Murabaha (cost-plus) totals per line using COST as base
            $financedLineItems = [];
            $financedTotal = 0.0;
            foreach ($lineItems as $li) {
                $lineFinanced = round(((float)$li['line_cost']) * (1 + $profitRate), 2);
                $unitFinanced = round($lineFinanced / max(1, (int)$li['quantity']), 2);
                $financedTotal += $lineFinanced;
                $financedLineItems[] = [
                    'product_id' => $li['product_id'],
                    'product_name' => $li['product_name'],
                    'quantity' => $li['quantity'],
                    'unit_price' => $unitFinanced, // financed unit price
                    'unit_cost' => $li['unit_cost'],
                    'line_total' => $lineFinanced,
                    'line_cost' => $li['line_cost'],
                    'line_profit' => round($lineFinanced - (float)$li['line_cost'], 2),
                ];
            }
            $financedTotal = round($financedTotal, 2);
            $totalProfit = round($financedTotal - $grandCost, 2);

            // Build simple equal installment schedule over 6–12 months
            $schedule = [];
            $kobo = (int) round($financedTotal * 100);
            $per = intdiv($kobo, $months);
            $rem = $kobo - ($per * $months);
            for ($i = 1; $i <= $months; $i++) {
                $amt = $per + ($i === $months ? $rem : 0);
                $due = now()->addMonthsNoOverflow($i)->startOfDay();
                $schedule[] = [
                    'installment' => $i,
                    'due_date' => $due->toDateString(),
                    'amount' => round($amt / 100, 2),
                    'status' => 'pending',
                ];
            }

            $meta = [
                'note' => !empty($note) ? $note : null,
                'financing' => [
                    'type' => 'murabaha',
                    'months' => $months,
                    'profit_rate' => $profitRate,
                    'schedule' => $schedule,
                ],
            ];

            $order = DB::transaction(function () use ($user, $reference, $financedTotal, $grandCost, $totalProfit, $meta, $financedLineItems) {
                $order = StoreOrder::create([
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'total_amount' => $financedTotal,
                    'total_cost' => $grandCost,
                    'total_profit' => $totalProfit,
                    'status' => 'murabaha_pending',
                    'meta' => $meta,
                ]);

                foreach ($financedLineItems as $li) {
                    StoreOrderItem::create(array_merge($li, [
                        'store_order_id' => $order->id,
                    ]));
                }

                return $order;
            });

            $order->load('items');

            return response()->json([
                'message' => 'Murabaha application submitted. We will contact you to fulfill this order and set up your installments.',
                'order' => $order,
                'balance' => (float) $user->fresh()->balance,
            ], 201);
        }

        // Cash purchase path (wallet debit)
        if ((float)$user->balance < $grandTotal) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $order = DB::transaction(function () use ($user, $lineItems, $grandTotal, $grandCost, $grandProfit, $reference, $note) {
            // Deduct wallet first to avoid race conditions
            $user->decrement('balance', $grandTotal);

            $meta = [];
            if (!empty($note)) {
                $meta['note'] = $note;
            }

            $order = StoreOrder::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'total_amount' => $grandTotal,
                'total_cost' => $grandCost,
                'total_profit' => $grandProfit,
                'status' => 'paid',
                'meta' => $meta,
            ]);

            foreach ($lineItems as $li) {
                StoreOrderItem::create(array_merge($li, [
                    'store_order_id' => $order->id,
                ]));
            }

            // Record wallet debit transaction
            $wtMeta = [
                'store_order_id' => $order->id,
                'items' => collect($lineItems)->map(fn ($li) => [
                    'product_id' => $li['product_id'],
                    'name' => $li['product_name'],
                    'qty' => $li['quantity'],
                    'unit_price' => $li['unit_price'],
                ])->values()->all(),
            ];
            if (!empty($note)) { $wtMeta['note'] = $note; }

            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $grandTotal,
                'reference' => $reference,
                'source' => 'store',
                'meta' => $wtMeta,
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

    /**
     * Pay next pending installment for a Murabaha (Buy on Credit) store order.
     * - Requires member ownership, valid 4-digit PIN, and sufficient wallet balance.
     * - Marks the next pending schedule item as paid and debits the wallet.
     * - Transitions status: murabaha_pending -> murabaha_active after first payment,
     *   and -> completed when all installments are paid.
     */
    public function payInstallment(Request $request, $id)
    {
        $validated = $request->validate([
            'pin' => ['required','regex:/^\d{4}$/'],
        ]);

        $user = $request->user();

        // Enforce Transaction PIN
        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }
        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        $order = StoreOrder::with('items')
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $meta = is_array($order->meta) ? $order->meta : [];
        $fin = $meta['financing'] ?? null;
        if (!is_array($fin) || ($fin['type'] ?? null) !== 'murabaha') {
            return response()->json(['message' => 'This order is not under Murabaha financing'], 422);
        }
        $schedule = $fin['schedule'] ?? [];
        if (!is_array($schedule) || empty($schedule)) {
            return response()->json(['message' => 'No installment schedule found'], 422);
        }

        // Find next pending installment
        $index = null;
        foreach ($schedule as $i => $item) {
            $st = strtolower((string)($item['status'] ?? ''));
            if ($st === 'pending') { $index = $i; break; }
        }
        if ($index === null) {
            return response()->json(['message' => 'All installments have been paid'], 422);
        }

        $amount = (float) ($schedule[$index]['amount'] ?? 0);
        if ($amount <= 0) {
            return response()->json(['message' => 'Invalid installment amount'], 422);
        }

        // Allow payment when order is in murabaha_* states
        $status = strtolower((string) $order->status);
        if (!\Illuminate\Support\Str::startsWith($status, 'murabaha_') && $status !== 'murabaha') {
            return response()->json(['message' => 'Installment payment not allowed for this order status'], 422);
        }

        if ((float) $user->balance < $amount) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = 'MURABAHAPAY_' . now()->format('YmdHis') . '_' . $user->id . '_' . $order->id . '_' . ($schedule[$index]['installment'] ?? ($index+1));

        DB::transaction(function () use ($user, $order, &$meta, &$schedule, $index, $amount, $reference) {
            // Debit wallet
            $user->decrement('balance', $amount);

            // Mark installment as paid
            $schedule[$index]['status'] = 'paid';
            $schedule[$index]['paid_at'] = now()->toDateTimeString();
            $meta['financing']['schedule'] = $schedule;

            // Update order status based on remaining installments
            $remaining = 0;
            foreach ($schedule as $it) {
                if (strtolower((string)($it['status'] ?? '')) === 'pending') { $remaining++; }
            }
            if ($remaining === 0) {
                $order->status = 'completed';
            } else {
                $order->status = 'murabaha_active';
            }
            $order->meta = $meta;
            $order->save();

            // Record wallet transaction
            $wtMeta = [
                'store_order_id' => $order->id,
                'installment' => $schedule[$index]['installment'] ?? ($index+1),
                'amount' => $amount,
                'type' => 'murabaha_installment',
            ];
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'source' => 'store_installment',
                'meta' => $wtMeta,
            ]);
        });

        $order->refresh()->load('items');

        return response()->json([
            'message' => 'Installment paid successfully',
            'order' => $order,
            'balance' => (float) $user->fresh()->balance,
        ]);
    }
}
