<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scheme;
use App\Models\WalletTransaction;
use App\Models\Contribution;
use App\Services\GoldSilverPriceService;
use App\Services\ZakatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GoldController extends Controller
{
    protected $goldService;
    protected $zakatService;

    public function __construct(GoldSilverPriceService $goldService, ZakatService $zakatService)
    {
        $this->goldService = $goldService;
        $this->zakatService = $zakatService;
    }

    public function getPrice()
    {
        $priceData = $this->goldService->getGoldPriceData();

        if (!$priceData) {
            return response()->json(['message' => 'Could not fetch current gold price.'], 503);
        }

        $basePrice = $priceData['base_price_ngn'];
        $buyPrice = $priceData['buy_price_ngn'];
        $sellPrice = $priceData['sell_price_ngn'];

        $user = auth()->user();

        $performance = $this->getPerformanceAndZakat($user, $sellPrice);
        $priceHistory = $this->goldService->getHistory('XAU', 7);

        return response()->json([
            'base_price' => $basePrice,
            'buy_price' => $buyPrice,
            'sell_price' => $sellPrice,
            'price_usd' => $priceData['base_price_usd'],
            'exchange_rate' => $priceData['exchange_rate'],
            'currency' => $priceData['currency'],
            'gold_balance' => (float) $user->gold_balance,
            'naira_balance' => (float) $user->balance,
            'current_value' => round($user->gold_balance * $sellPrice, 2), // Current value is what they'd get if they sold
            'performance' => $performance['performance'],
            'zakat' => $performance['zakat'],
            'price_history' => $priceHistory
        ]);
    }

    private function getPerformanceAndZakat($user, $sellPrice)
    {
        $scheme = Scheme::where('name', 'Digital Gold')->first();

        // Performance
        $totalSpent = 0;
        $totalGramsBought = 0;
        $avgBuyPrice = 0;
        $profitLoss = 0;
        $roi = 0;

        if ($scheme) {
            $buys = Contribution::where('user_id', $user->id)
                ->where('scheme_id', $scheme->id)
                ->where('amount', '>', 0)
                ->where('status', 'success')
                ->get();

            if ($buys->count() > 0) {
                $totalSpent = $buys->sum('amount');
                $totalGramsBought = $buys->sum('units');
                if ($totalGramsBought > 0) {
                    $avgBuyPrice = $totalSpent / $totalGramsBought;
                }
            }
        }

        if ($user->gold_balance > 0 && $avgBuyPrice > 0) {
            $costBasis = $user->gold_balance * $avgBuyPrice;
            $currentValue = $user->gold_balance * $sellPrice;
            $profitLoss = $currentValue - $costBasis;
            $roi = ($profitLoss / $costBasis) * 100;
        }

        // Zakat Report (Using the new automated logic)
        $zakatEstimate = $this->zakatService->getEstimate($user);

        // Normalize data for frontend
        $zakatEstimate['crossed_on'] = optional($zakatEstimate['crossed_on'])->toDateTimeString();
        $zakatEstimate['eligible_on'] = optional($zakatEstimate['eligible_on'])->toDateTimeString();
        $zakatEstimate['last_paid_at'] = optional($zakatEstimate['last_paid_at'])->toDateTimeString();

        $nisabValue = $zakatEstimate['nisab'] ?? 0;
        $totalAssets = $zakatEstimate['base'] ?? 0;
        $progress = $nisabValue > 0 ? round(min(($totalAssets / $nisabValue) * 100, 100), 2) : 0;
        $gramsToNisab = 0;
        if ($totalAssets < $nisabValue && $sellPrice > 0) {
            $gramsToNisab = round(($nisabValue - $totalAssets) / $sellPrice, 6);
        }

        return [
            'performance' => [
                'avg_buy_price' => round($avgBuyPrice, 2),
                'total_profit_loss' => round($profitLoss, 2),
                'roi_percent' => round($roi, 2),
                'total_grams_bought' => round($totalGramsBought, 6),
                'total_invested' => round($totalSpent, 2)
            ],
            'zakat' => [
                'nisab_grams' => config('zakat.nisab_gold_grams', 85),
                'progress_percent' => $progress,
                'is_eligible' => $zakatEstimate['eligible'] ?? false,
                'grams_to_nisab' => $gramsToNisab,
                'report' => $zakatEstimate
            ]
        ];
    }

    public function buy(Request $request)
    {
        $scheme = Scheme::where('name', 'Digital Gold')->first();
        $minAmount = $scheme ? $scheme->min_amount : 1000;

        $request->validate([
            'amount_naira' => "required|numeric|min:$minAmount",
            'pin' => 'required|string'
        ]);

        $user = auth()->user();

        if (!$user->verifyTransactionPin($request->pin)) {
            return response()->json(['message' => 'Invalid transaction PIN.'], 403);
        }

        if ($user->balance < $request->amount_naira) {
            return response()->json(['message' => 'Insufficient wallet balance.'], 400);
        }

        $priceData = $this->goldService->getGoldPriceData();
        $buyPrice = $priceData['buy_price_ngn'] ?? null;

        if (!$buyPrice) {
            return response()->json(['message' => 'Could not fetch current gold price. Please try again later.'], 503);
        }

        // Apply buy fee
        $feeRate = config('zakat.gold_buy_fee', 0.005);
        $fee = $request->amount_naira * $feeRate;
        $netAmount = $request->amount_naira - $fee;
        $grams = round($netAmount / $buyPrice, 6);

        DB::transaction(function () use ($user, $request, $grams, $buyPrice, $scheme, $fee, $priceData) {
            // Deduct full amount from wallet
            $user->decrement('balance', $request->amount_naira);

            // Add to gold balance
            $user->increment('gold_balance', $grams);

            // Record wallet transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $request->amount_naira,
                'reference' => 'GOLD-BUY-' . time() . '-' . uniqid(),
                'source' => 'gold_purchase',
                'meta' => [
                    'grams' => $grams,
                    'price_at_purchase' => $buyPrice,
                    'price_usd' => $priceData['base_price_usd'] ?? null,
                    'exchange_rate' => $priceData['exchange_rate'] ?? null,
                    'fee_charged' => $fee,
                    'net_amount' => $request->amount_naira - $fee
                ]
            ]);

            // Record contribution for tracking
            if ($scheme) {
                Contribution::create([
                    'user_id' => $user->id,
                    'scheme_id' => $scheme->id,
                    'amount' => $request->amount_naira,
                    'units' => $grams,
                    'status' => 'success',
                    'reference' => 'GOLD-SAVE-' . time() . '-' . uniqid()
                ]);
            }
        });

        return response()->json([
            'message' => "Successfully purchased $grams grams of gold.",
            'gold_balance' => (float) $user->refresh()->gold_balance,
            'naira_balance' => (float) $user->balance
        ]);
    }

    public function sell(Request $request)
    {
        $request->validate([
            'grams' => 'required|numeric|min:0.000001',
            'pin' => 'required|string'
        ]);

        $user = auth()->user();

        if (!$user->verifyTransactionPin($request->pin)) {
            return response()->json(['message' => 'Invalid transaction PIN.'], 403);
        }

        if ($user->gold_balance < $request->grams) {
            return response()->json(['message' => 'Insufficient gold balance.'], 400);
        }

        $priceData = $this->goldService->getGoldPriceData();
        $sellPrice = $priceData['sell_price_ngn'] ?? null;

        if (!$sellPrice) {
            return response()->json(['message' => 'Could not fetch current gold price. Please try again later.'], 503);
        }

        $grossAmount = $request->grams * $sellPrice;
        $feeRate = config('zakat.gold_sell_fee', 0.005);
        $fee = $grossAmount * $feeRate;
        $netAmount = round($grossAmount - $fee, 2);

        DB::transaction(function () use ($user, $request, $netAmount, $sellPrice, $fee, $priceData) {
            // Deduct from gold balance
            $user->decrement('gold_balance', $request->grams);

            // Add net amount to wallet balance
            $user->increment('balance', $netAmount);

            // Record wallet transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $netAmount,
                'reference' => 'GOLD-SELL-' . time() . '-' . uniqid(),
                'source' => 'gold_sale',
                'meta' => [
                    'grams' => $request->grams,
                    'price_at_sale' => $sellPrice,
                    'price_usd' => $priceData['base_price_usd'] ?? null,
                    'exchange_rate' => $priceData['exchange_rate'] ?? null,
                    'fee_charged' => $fee,
                    'gross_amount' => $request->grams * $sellPrice
                ]
            ]);

            // Record negative contribution for gold scheme tracking
            $scheme = Scheme::where('name', 'Digital Gold')->first();
            if ($scheme) {
                Contribution::create([
                    'user_id' => $user->id,
                    'scheme_id' => $scheme->id,
                    'amount' => -$netAmount,
                    'units' => -$request->grams,
                    'status' => 'success',
                    'reference' => 'GOLD-SELL-' . time() . '-' . uniqid()
                ]);
            }
        });

        return response()->json([
            'message' => "Successfully sold $request->grams grams of gold for ₦" . number_format($netAmount, 2),
            'gold_balance' => (float) $user->refresh()->gold_balance,
            'naira_balance' => (float) $user->balance
        ]);
    }

    public function history()
    {
        $user = auth()->user();
        $scheme = Scheme::where('name', 'Digital Gold')->first();

        if (!$scheme) {
            return response()->json([]);
        }

        $history = Contribution::where('user_id', $user->id)
            ->where('scheme_id', $scheme->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($history);
    }

    public function export()
    {
        $user = auth()->user();
        $scheme = Scheme::where('name', 'Digital Gold')->first();

        if (!$scheme) {
            return response()->json(['error' => 'Scheme not found'], 404);
        }

        $transactions = Contribution::where('user_id', $user->id)
            ->where('scheme_id', $scheme->id)
            ->latest()
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gold_transactions.csv"',
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Type', 'Amount (Naira)', 'Units (Grams)', 'Status']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->amount > 0 ? 'Buy' : 'Sell',
                    abs($tx->amount),
                    $tx->units,
                    $tx->status
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
