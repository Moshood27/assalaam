<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WalletTransaction;
use App\Models\IncomeEntry;
use App\Models\ExpenseEntry;
use App\Models\CharityEntry;
use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\StoreOrder;
use App\Models\ProjectProfit;
use App\Models\ProjectProfitPayout;
use App\Observers\WalletTransactionObserver;
use App\Observers\IncomeEntryObserver;
use App\Observers\ExpenseEntryObserver;
use App\Observers\CharityEntryObserver;
use App\Observers\ContributionObserver;
use App\Observers\QardHasanObserver;
use App\Observers\QardHasanRepaymentObserver;
use App\Observers\StoreOrderObserver;
use App\Observers\ProjectProfitObserver;
use App\Observers\ProjectProfitPayoutObserver;

class LedgerBackfill extends Command
{
    protected $signature = 'ledger:backfill {--force : Force backfill even if ledger_journal_id is set}';
    protected $description = 'Backfill historical data into the double-entry ledger';

    public function handle()
    {
        $force = $this->option('force');

        if ($force && !$this->confirm('This will potentially create duplicate entries. Continue?')) {
            return;
        }

        $this->info("Starting Ledger Backfill...");

        $this->process(WalletTransaction::class, WalletTransactionObserver::class, $force);
        $this->process(IncomeEntry::class, IncomeEntryObserver::class, $force);
        $this->process(ExpenseEntry::class, ExpenseEntryObserver::class, $force);
        $this->process(CharityEntry::class, CharityEntryObserver::class, $force);
        $this->process(Contribution::class, ContributionObserver::class, $force);
        $this->process(QardHasan::class, QardHasanObserver::class, $force);
        $this->process(QardHasanRepayment::class, QardHasanRepaymentObserver::class, $force);
        $this->process(StoreOrder::class, StoreOrderObserver::class, $force);
        $this->process(ProjectProfit::class, ProjectProfitObserver::class, $force);
        $this->process(ProjectProfitPayout::class, ProjectProfitPayoutObserver::class, $force);

        $this->info("Ledger Backfill completed!");
    }

    protected function process($modelClass, $observerClass, $force)
    {
        $this->info("Processing " . class_basename($modelClass) . "...");
        $observer = app($observerClass);

        $query = $modelClass::query();
        if (!$force) {
            $query->whereNull('ledger_journal_id');
        }

        $count = 0;
        $query->chunk(100, function ($records) use ($observer, &$count) {
            foreach ($records as $record) {
                try {
                    // For models like QardHasan or StoreOrder, we might need to check status
                    // but the observers usually handle that.
                    if (method_exists($observer, 'created')) {
                        $observer->created($record);
                    } elseif (method_exists($observer, 'updated')) {
                        // Some observers use 'updated' for status changes (e.g. Contribution)
                        // In backfill, we treat them as 'just happened'
                        $observer->updated($record);
                    }
                    $count++;
                } catch (\Exception $e) {
                    $this->error("Error processing " . class_basename($record) . " ID {$record->id}: " . $e->getMessage());
                }
            }
        });

        $this->info("Finished " . class_basename($modelClass) . ": $count records processed.");
    }
}
