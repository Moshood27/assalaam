<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use App\Services\GoldSilverPriceService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberBalancesBranchReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Member Balances Branch Report';
    protected static ?int $navigationSort = 17;

    protected static string $view = 'filament.pages.member-balances-branch-report';

    public function getSubheading(): ?string
    {
        return 'Consolidated member balances (Savings, Shares, Gold, etc.) grouped by branch.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_user');
    }

    public array $report = [
        'branches' => [],
        'grand_total_savings' => 0,
        'grand_total_shares' => 0,
        'grand_total_gold_weight' => 0,
        'grand_total_gold_value' => 0,
        'grand_total_other' => 0,
        'grand_total_members_count' => 0,
    ];

    public ?int $branchId = null;

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user->hasRole('super_admin')) {
            $this->branchId = $user->branch_id;
        }
        $this->refreshReport();
    }

    public function updatedBranchId(): void
    {
        $this->refreshReport();
    }

    public function refreshReport(): void
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $goldSvc = app(GoldSilverPriceService::class);
        $goldPrice = $goldSvc->getGoldPrice();
        $user = auth()->user();

        $targetBranchId = $user->hasRole('super_admin') ? $this->branchId : $user->branch_id;
        $this->report = $svc->buildBranchMemberBalancesReport($targetBranchId, $goldPrice);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="member-balances-branch-report.csv"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Branch', 'Member', 'Membership #', 'Savings', 'Shares', 'Gold (g)', 'Gold (Val)', 'Other Funds', 'Total Wealth']);

            foreach ($data['branches'] as $branch) {
                foreach ($branch['members'] as $member) {
                    fputcsv($out, [
                        $branch['branch_name'],
                        $member['member_name'],
                        $member['membership_number'],
                        number_format($member['savings'], 2, '.', ''),
                        number_format($member['shares'], 2, '.', ''),
                        number_format($member['gold_weight'], 2, '.', ''),
                        number_format($member['gold_value'], 2, '.', ''),
                        number_format($member['other_funds'], 2, '.', ''),
                        number_format($member['total_wealth'], 2, '.', ''),
                    ]);
                }
                // Branch total
                fputcsv($out, [
                    $branch['branch_name'] . ' TOTAL',
                    '',
                    '',
                    number_format($branch['total_savings'], 2, '.', ''),
                    number_format($branch['total_shares'], 2, '.', ''),
                    number_format($branch['total_gold_weight'], 2, '.', ''),
                    number_format($branch['total_gold_value'], 2, '.', ''),
                    number_format($branch['total_other'], 2, '.', ''),
                    number_format($branch['total_savings'] + $branch['total_shares'] + $branch['total_gold_value'] + $branch['total_other'], 2, '.', ''),
                ]);
                fputcsv($out, []);
            }

            // Grand total
            if (count($data['branches']) > 1) {
                fputcsv($out, [
                    'GRAND TOTAL',
                    $data['grand_total_members_count'] . ' members',
                    '',
                    number_format($data['grand_total_savings'], 2, '.', ''),
                    number_format($data['grand_total_shares'], 2, '.', ''),
                    number_format($data['grand_total_gold_weight'], 2, '.', ''),
                    number_format($data['grand_total_gold_value'], 2, '.', ''),
                    number_format($data['grand_total_other'], 2, '.', ''),
                    number_format($data['grand_total_savings'] + $data['grand_total_shares'] + $data['grand_total_gold_value'] + $data['grand_total_other'], 2, '.', ''),
                ]);
            }

            fclose($out);
        }, 'member-balances-branch-report.csv', $headers);
    }
}
