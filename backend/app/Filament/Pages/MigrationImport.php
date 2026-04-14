<?php

namespace App\Filament\Pages;

use App\Imports\UsersImport;
use App\Imports\BalancesImport;
use App\Imports\LoansImport;
use App\Imports\PassbookImport;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\QardHasan;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;

class MigrationImport extends Page implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    protected static ?string $navigationGroup = 'Admin Tools';
    protected static ?string $navigationLabel = 'System Migration';
    protected static ?int $navigationSort = 95;

    protected static string $view = 'filament.pages.migration-import';

    public $membersFile;
    public $balancesFile;
    public $loansFile;
    public $passbookFile;
    public $migrationDate;

    public function mount()
    {
        $this->migrationDate = now()->format('Y-m-d');
    }

    public function getSubheading(): ?string
    {
        return 'Migrate data from Paper/Excel records to the digital system. This is a critical action.';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        // Allow if user is explicitly marked as admin OR has the super_admin role
        return $user->is_admin === true || $user->hasRole('super_admin');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reconcile')
                ->label('Run Reconciliation')
                ->color('success')
                ->icon('heroicon-o-check-badge')
                ->action(fn () => $this->reconcile()),

            Action::make('downloadReport')
                ->label('Download PDF Report')
                ->color('info')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => $this->downloadReport()),

            Action::make('sendOnboardingSms')
                ->label('Send Onboarding SMS')
                ->color('warning')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Send SMS to Migrated Members')
                ->modalDescription('This will send an onboarding SMS to all members who have been migrated but NOT yet verified their account. Ensure your SMS provider is configured.')
                ->action(fn () => $this->sendOnboardingSms()),
        ];
    }

    public function sendOnboardingSms()
    {
        $users = User::whereNotNull('migrated_at')
            ->whereNull('verified_at')
            ->whereNotNull('phone')
            ->get();

        if ($users->isEmpty()) {
            Notification::make()->info()->title('No unverified migrated members found.')->send();
            return;
        }

        $smsService = app(\App\Services\SmsService::class);
        $count = 0;

        foreach ($users as $user) {
            $message = "Welcome to Attaqwa Pay! Your digital account is ready. Log in with your phone number and verify your opening balance. Download the app today.";
            if ($smsService->send($user->phone, $message)) {
                $count++;
            }
        }

        Notification::make()
            ->success()
            ->title("SMS Broadcast Complete")
            ->body("Successfully sent onboarding SMS to {$count} members.")
            ->send();
    }

    public function downloadReport()
    {
        $memberCount = User::whereNotNull('migrated_at')->count();
        $totalSavings = User::sum('ordinary_savings');
        $totalShares = User::sum('shares_capital');
        $totalFines = User::sum('outstanding_fines');
        $totalWallet = User::sum('balance');
        $totalGold = User::sum('gold_balance');

        $loanCount = QardHasan::where('status', 'active')->count();
        $totalLoans = QardHasan::where('status', 'active')->sum('principal_amount');
        $paidLoans = QardHasan::where('status', 'active')->sum('paid_amount');
        $remainingLoans = $totalLoans - $paidLoans;

        // Sum other funds directly from User columns for accuracy
        $otherFundsColumns = [
            'building_balance', 'development_fund_balance', 'agm_balance',
            'loan_repayment_balance', 'fine_balance', 'welfare_balance',
            'lateness_balance', 'stationery_balance', 'loan_form_balance',
            'others_balance', 'id_card_balance', 'emergency_balance',
            'entrance_balance', 'h_savings_balance', 'investment_balance',
            'group_savings_balance'
        ];

        $otherFunds = 0;
        foreach ($otherFundsColumns as $col) {
            $otherFunds += User::sum($col);
        }

        $takafulFunds = \App\Models\TakafulContribution::where('reference', 'LIKE', 'MIG-%TAKF-%')->sum('amount');

        $data = [
            'date' => now()->format('d M, Y H:i'),
            'memberCount' => $memberCount,
            'totalWallet' => $totalWallet,
            'totalSavings' => $totalSavings,
            'totalShares' => $totalShares,
            'totalFines' => $totalFines,
            'totalGold' => $totalGold,
            'otherFunds' => $otherFunds,
            'takafulFunds' => $takafulFunds,
            'buildingBalance' => User::sum('building_balance'),
            'developmentFundBalance' => User::sum('development_fund_balance'),
            'agmBalance' => User::sum('agm_balance'),
            'loanRepaymentBalance' => User::sum('loan_repayment_balance'),
            'fineBalance' => User::sum('fine_balance'),
            'welfareBalance' => User::sum('welfare_balance'),
            'latenessBalance' => User::sum('lateness_balance'),
            'stationeryBalance' => User::sum('stationery_balance'),
            'loanFormBalance' => User::sum('loan_form_balance'),
            'othersBalance' => User::sum('others_balance'),
            'idCardBalance' => User::sum('id_card_balance'),
            'emergencyBalance' => User::sum('emergency_balance'),
            'entranceBalance' => User::sum('entrance_balance'),
            'hSavingsBalance' => User::sum('h_savings_balance'),
            'investmentBalance' => User::sum('investment_balance'),
            'groupSavingsBalance' => User::sum('group_savings_balance'),
            'loanCount' => $loanCount,
            'totalLoans' => $totalLoans,
            'paidLoans' => $paidLoans,
            'remainingLoans' => $remainingLoans,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.migration-reconciliation', $data);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'Attaqwa_Migration_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function importMembers()
    {
        if (!$this->membersFile) {
            Notification::make()->warning()->title('Please upload a Members Master file.')->send();
            return;
        }

        try {
            $date = \Carbon\Carbon::parse($this->migrationDate);
            Excel::import(new UsersImport($date), $this->membersFile);
            Notification::make()->success()->title('Members migrated successfully.')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Migration failed: ' . $e->getMessage())->send();
        }
    }

    public function importBalances()
    {
        if (!$this->balancesFile) {
            Notification::make()->warning()->title('Please upload a Balances Master file.')->send();
            return;
        }

        try {
            Excel::import(new BalancesImport(\Carbon\Carbon::parse($this->migrationDate)), $this->balancesFile);
            Notification::make()->success()->title('Balances migrated successfully.')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Migration failed: ' . $e->getMessage())->send();
        }
    }

    public function importLoans()
    {
        if (!$this->loansFile) {
            Notification::make()->warning()->title('Please upload a Loan Master file.')->send();
            return;
        }

        try {
            Excel::import(new LoansImport(\Carbon\Carbon::parse($this->migrationDate)), $this->loansFile);
            Notification::make()->success()->title('Loans migrated successfully.')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Migration failed: ' . $e->getMessage())->send();
        }
    }

    public function importPassbook()
    {
        if (!$this->passbookFile) {
            Notification::make()->warning()->title('Please upload a Passbook Master file.')->send();
            return;
        }

        try {
            Excel::import(new PassbookImport(), $this->passbookFile);
            Notification::make()->success()->title('Passbook history migrated and reconciled successfully.')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('Migration failed: ' . $e->getMessage())->send();
        }
    }

    public function reconcile()
    {
        $totalSavings = User::sum('ordinary_savings');
        $totalShares = User::sum('shares_capital');
        $totalFines = User::sum('outstanding_fines');
        $totalWallet = User::sum('balance');
        $totalGold = User::sum('gold_balance');

        // Sum other funds directly from User columns
        $otherFundsColumns = [
            'building_balance', 'development_fund_balance', 'agm_balance',
            'loan_repayment_balance', 'fine_balance', 'welfare_balance',
            'lateness_balance', 'stationery_balance', 'loan_form_balance',
            'others_balance', 'id_card_balance', 'emergency_balance',
            'entrance_balance', 'h_savings_balance', 'investment_balance',
            'group_savings_balance'
        ];

        $otherFunds = 0;
        foreach ($otherFundsColumns as $col) {
            $otherFunds += User::sum($col);
        }

        // Add Takaful migration contributions
        $takafulFunds = \App\Models\TakafulContribution::where('reference', 'LIKE', 'MIG-%TAKF-%')->sum('amount');

        $totalLoans = QardHasan::where('status', 'active')->sum('principal_amount');
        $paidLoans = QardHasan::where('status', 'active')->sum('paid_amount');
        $remainingLoans = $totalLoans - $paidLoans;

        $totalLiabilities = $totalWallet + $totalSavings + $totalShares + $otherFunds + $takafulFunds;

        Notification::make()
            ->title('Reconciliation Report')
            ->body("Total Financial Liabilities: ₦" . number_format($totalLiabilities, 2) . "\n" .
                  "----------------------------\n" .
                  "Total Wallet Balance: ₦" . number_format($totalWallet, 2) . "\n" .
                  "Total Savings: ₦" . number_format($totalSavings, 2) . "\n" .
                  "Total Shares: ₦" . number_format($totalShares, 2) . "\n" .
                  "Total Other Funds: ₦" . number_format($otherFunds, 2) . "\n" .
                  "Total Takaful: ₦" . number_format($takafulFunds, 2) . "\n" .
                  "Total Digital Gold: " . number_format($totalGold, 4) . "g\n" .
                  "Total Outstanding Fines: ₦" . number_format($totalFines, 2) . "\n" .
                  "Outstanding Loans: ₦" . number_format($remainingLoans, 2))
            ->success()
            ->persistent()
            ->send();
    }
}
