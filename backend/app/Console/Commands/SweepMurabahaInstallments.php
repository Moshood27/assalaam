<?php

namespace App\Console\Commands;

use App\Models\StoreOrder;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SweepMurabahaInstallments extends Command
{
    protected $signature = 'murabaha:sweep {--dry-run : Do not actually debit, only log what would happen}';
    protected $description = 'Automatically deduct due Murabaha installments from member wallets when balance is available';

    public function handle(): int
    {
        $today = now()->toDateString();
        $dry = (bool) $this->option('dry-run');
        $count = 0;

        StoreOrder::query()
            ->where(function($q){
                $q->where('status', 'like', 'murabaha%')->orWhere('status', 'murabaha');
            })
            ->orderBy('id')
            ->chunkById(200, function ($orders) use (&$count, $today, $dry) {
                foreach ($orders as $order) {
                    $meta = is_array($order->meta) ? $order->meta : [];
                    $fin = $meta['financing'] ?? null;
                    if (!is_array($fin) || ($fin['type'] ?? null) !== 'murabaha') {
                        continue;
                    }
                    if (isset($fin['autopay_enabled']) && !$fin['autopay_enabled']) {
                        continue;
                    }
                    $schedule = $fin['schedule'] ?? [];
                    if (!is_array($schedule) || empty($schedule)) {
                        continue;
                    }
                    // Find next pending/partial installment that is due today or earlier
                    $index = null;
                    foreach ($schedule as $i => $item) {
                        $st = strtolower((string)($item['status'] ?? ''));
                        $due = (string)($item['due_date'] ?? '');
                        if (($st === 'pending' || $st === 'partial') && $due !== '' && $due <= $today) {
                            $index = $i; break;
                        }
                    }
                    if ($index === null) {
                        continue;
                    }

                    $user = $order->user;
                    if (!$user) { continue; }

                    // Compute remaining and min due
                    $nextAmount = (float) ($schedule[$index]['amount'] ?? 0);
                    $alreadyPaid = (float) ($schedule[$index]['paid_amount'] ?? 0);
                    $nextRemaining = max(0.0, round($nextAmount - $alreadyPaid, 2));
                    if ($nextRemaining <= 0) { continue; }

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
                    if ($totalRemaining <= 0) { continue; }

                    $minDueForNext = min($nextRemaining, $totalRemaining);

                    if ((float) $user->balance + 0.00001 < $minDueForNext) {
                        // Not enough balance to sweep this installment
                        continue;
                    }

                    $toApply = $minDueForNext; // pay exactly the next due amount

                    if ($dry) {
                        $this->info("[DRY] Would auto-debit user {$user->id} order {$order->id} amount {$toApply}");
                        $count++;
                        continue;
                    }

                    $reference = 'MURASWEEP_' . now()->format('YmdHis') . '_' . $user->id . '_' . $order->id;

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
                        $status = strtolower((string) $order->status);
                        if ($remainingAmt <= 0.0) {
                            $order->status = 'completed';
                        } else {
                            // Activate after first payment
                            if (!Str::startsWith($status, 'murabaha_active')) {
                                $order->status = 'murabaha_active';
                            }
                        }
                        $order->meta = $meta;
                        $order->save();

                        // Record wallet transaction (auto)
                        $wtMeta = [
                            'store_order_id' => $order->id,
                            'amount' => $toApply,
                            'type' => 'murabaha_installment',
                            'auto' => true,
                            'applied' => $covered,
                        ];
                        WalletTransaction::create([
                            'user_id' => $user->id,
                            'type' => 'debit',
                            'amount' => $toApply,
                            'reference' => $reference,
                            'source' => 'store_installment_auto',
                            'meta' => $wtMeta,
                        ]);
                    });
                    $count++;
                }
            });

        $this->info("Completed Murabaha sweep. Debits: {$count}");
        return self::SUCCESS;
    }
}
