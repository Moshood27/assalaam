<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContributionBranchReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Contribution Branch Report';
    protected static ?int $navigationSort = 16;

    protected static string $view = 'filament.pages.contribution-branch-report';

    public function getSubheading(): ?string
    {
        return 'Total contributions by members grouped by branch.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_contribution');
    }

    public array $report = [
        'branches' => [],
        'grand_total_amount' => 0,
        'grand_total_members_count' => 0,
    ];

    public ?int $branchId = null;
    public ?string $from = null;
    public ?string $to = null;

    public function mount(): void
    {
        $this->to = now()->toDateString();
        $user = auth()->user();
        if (!$user->hasRole('super_admin')) {
            $this->branchId = $user->branch_id;
        }
        $this->refreshReport();
    }

    public function updated($name): void
    {
        if (in_array($name, ['branchId', 'from', 'to'])) {
            $this->refreshReport();
        }
    }

    public function refreshReport(): void
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $user = auth()->user();

        $targetBranchId = $user->hasRole('super_admin') ? $this->branchId : $user->branch_id;
        $this->report = $svc->buildBranchContributionReport($targetBranchId, $this->from, $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="contribution-branch-report.csv"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Branch', 'Member', 'Membership #', 'Total Contributed', 'Last Contribution']);

            foreach ($data['branches'] as $branch) {
                foreach ($branch['members'] as $member) {
                    fputcsv($out, [
                        $branch['branch_name'],
                        $member['member_name'],
                        $member['membership_number'],
                        number_format($member['total_contributed'], 2, '.', ''),
                        $member['last_contribution_date'] ? $member['last_contribution_date'] : 'N/A',
                    ]);
                }
                // Branch total
                fputcsv($out, [
                    $branch['branch_name'] . ' TOTAL',
                    '',
                    '',
                    number_format($branch['total_amount'], 2, '.', ''),
                    '',
                ]);
                fputcsv($out, []); // Empty line between branches
            }

            // Grand total
            if (count($data['branches']) > 1) {
                fputcsv($out, [
                    'GRAND TOTAL',
                    $data['grand_total_members_count'] . ' members',
                    '',
                    number_format($data['grand_total_amount'], 2, '.', ''),
                    '',
                ]);
            }

            fclose($out);
        }, 'contribution-branch-report.csv', $headers);
    }
}
