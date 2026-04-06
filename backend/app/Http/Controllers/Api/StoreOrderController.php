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

    public function eligibility(Request $request)
    {
        $user = $request->user();
        $calc = $user->savingsSharesEligibility();

        return response()->json([
            'limit' => (float) ($calc['base'] ?? 0),
            'has_active_financing' => $user->hasActiveStoreFinancing(),
            'has_active_loan' => $user->hasActiveLoan(),
            'savings' => (float) ($calc['savings'] ?? 0),
            'shares' => (float) ($calc['shares'] ?? 0),
        ]);
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

            // Check stock
            if ($p->track_stock && $p->stock_quantity < $qty) {
                return response()->json(['message' => "Insufficient stock for product: {$p->name}"], 422);
            }

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
            // Check for existing active Murabaha/Mudarabah financing
            if ($user->hasActiveStoreFinancing()) {
                return response()->json([
                    'message' => 'You already have an active store financing order. Please complete it first.'
                ], 422);
            }

            // Check for any other active loan (QardHasan)
            if ($user->hasActiveLoan()) {
                return response()->json([
                    'message' => 'You have an active loan. Please complete all pending loans before taking store financing.'
                ], 422);
            }

            // Check borrowing limit: Savings + Shares (returned as 'base' in eligibility calc)
            $calc = $user->savingsSharesEligibility();
            $limit = (float) ($calc['base'] ?? 0);

            if ($grandTotal > $limit) {
                return response()->json([
                    'message' => "Your borrowing limit is ₦ " . number_format($limit, 2) . ". Please choose a lower priced product or more savings/shares."
                ], 422);
            }

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

                    // Decrement stock if tracking
                    Product::where('id', $li['product_id'])
                        ->where('track_stock', true)
                        ->decrement('stock_quantity', $li['quantity']);
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

                // Decrement stock if tracking
                Product::where('id', $li['product_id'])
                    ->where('track_stock', true)
                    ->decrement('stock_quantity', $li['quantity']);
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
            'amount' => 'nullable|numeric|min:0.01',
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

        // Find next pending or partial installment
        $index = null;
        foreach ($schedule as $i => $item) {
            $st = strtolower((string)($item['status'] ?? ''));
            if ($st === 'pending' || $st === 'partial') { $index = $i; break; }
        }
        if ($index === null) {
            return response()->json(['message' => 'All installments have been paid'], 422);
        }

        $nextAmount = (float) ($schedule[$index]['amount'] ?? 0);
        if ($nextAmount <= 0) {
            return response()->json(['message' => 'Invalid installment amount'], 422);
        }
        $alreadyPaid = (float) ($schedule[$index]['paid_amount'] ?? 0);

        // Compute total remaining across all schedule
        $totalRemaining = 0.0;
        foreach ($schedule as $it) {
            $amt = (float)($it['amount'] ?? 0);
            $pd = (float)($it['paid_amount'] ?? 0);
            $st = strtolower((string)($it['status'] ?? ''));
            if ($st !== 'paid') {
                $totalRemaining += max(0.0, round($amt - $pd, 2));
            }
        }
        $totalRemaining = round($totalRemaining, 2);
        if ($totalRemaining <= 0) {
            return response()->json(['message' => 'All installments have been paid'], 422);
        }

        // Minimum acceptable is at least one full monthly installment, except the final remaining which may be < monthly
        $minDueForNext = $totalRemaining < $nextAmount ? $totalRemaining : $nextAmount;

        // Determine amount to apply: default is exactly next due if not provided
        $toApply = isset($validated['amount']) ? (float)$validated['amount'] : $minDueForNext;
        $toApply = round($toApply, 2);

        if ($toApply < $minDueForNext) {
            return response()->json(['message' => 'Minimum payment is ₦ ' . number_format($minDueForNext, 2)], 422);
        }
        // Do not accept beyond total remaining
        if ($toApply > $totalRemaining) {
            $toApply = $totalRemaining;
        }

        // Allow payment when order is in murabaha_* states
        $status = strtolower((string) $order->status);
        if (!\Illuminate\Support\Str::startsWith($status, 'murabaha_') && $status !== 'murabaha') {
            return response()->json(['message' => 'Installment payment not allowed for this order status'], 422);
        }

        if ((float) $user->balance < $toApply) {
            return response()->json(['message' => 'Insufficient Coop Balance'], 422);
        }

        $reference = 'MURABAHAPAY_' . now()->format('YmdHis') . '_' . $user->id . '_' . $order->id;

        DB::transaction(function () use ($user, $order, &$meta, &$schedule, $toApply, $reference) {
            // Debit wallet
            $user->decrement('balance', $toApply);

            $remainingToApply = $toApply;
            $covered = [];
            foreach ($schedule as $i => &$it) {
                if ($remainingToApply <= 0) break;
                $st = strtolower((string)($it['status'] ?? ''));
                if ($st === 'paid') continue;

                $amt = (float)($it['amount'] ?? 0);
                $pd = (float)($it['paid_amount'] ?? 0);
                $left = max(0.0, round($amt - $pd, 2));
                if ($left <= 0) { $it['status'] = 'paid'; $it['paid_at'] = $it['paid_at'] ?? now()->toDateTimeString(); continue; }

                $apply = min($left, $remainingToApply);
                $pd = round($pd + $apply, 2);
                $it['paid_amount'] = $pd;
                if ($pd + 0.00001 >= $amt) {
                    $it['status'] = 'paid';
                    $it['paid_at'] = now()->toDateTimeString();
                } else {
                    $it['status'] = 'partial';
                }
                $remainingToApply = round($remainingToApply - $apply, 2);

                $covered[] = [
                    'installment' => $it['installment'] ?? ($i+1),
                    'applied' => $apply,
                    'status' => $it['status'],
                ];
            }
            unset($it);

            // Update totals in meta
            $totalPaid = 0.0;
            foreach ($schedule as $it2) {
                $amt2 = (float)($it2['amount'] ?? 0);
                $pd2 = (float)($it2['paid_amount'] ?? 0);
                $totalPaid += min($amt2, $pd2);
            }
            $totalPaid = round($totalPaid, 2);
            $remainingAmt = max(0.0, round(((float)$order->total_amount) - $totalPaid, 2));

            $meta['financing']['schedule'] = $schedule;
            $meta['financing']['total_paid'] = $totalPaid;
            $meta['financing']['remaining'] = $remainingAmt;

            // Update order status
            if ($remainingAmt <= 0.0) {
                $order->status = 'completed';
            } else {
                $order->status = 'murabaha_active';
            }
            $order->meta = $meta;
            $order->save();

            // Record wallet transaction
            $wtMeta = [
                'store_order_id' => $order->id,
                'amount' => $toApply,
                'type' => 'murabaha_installment',
                'applied' => $covered,
            ];
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $toApply,
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
