<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchemeBranchReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Scheme Branch Report';
    protected static ?int $navigationSort = 18;

    protected static string $view = 'filament.pages.scheme-branch-report';

    public function getSubheading(): ?string
    {
        return 'Total contributions by members grouped by branch and scheme.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_contribution');
    }

    public array $report = [
        'branches' => [],
        'schemes' => [],
        'grand_totals' => [],
        'grand_total_all' => 0,
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
        $this->report = $svc->buildBranchSchemeReport($targetBranchId, $this->from, $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="scheme-branch-report.csv"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');

            $headerRow = ['Branch', 'Member', 'Membership #'];
            foreach ($data['schemes'] as $scheme) {
                $headerRow[] = $scheme['name'];
            }
            $headerRow[] = 'Total';

            fputcsv($out, $headerRow);

            foreach ($data['branches'] as $branch) {
                foreach ($branch['members'] as $member) {
                    $row = [
                        $branch['branch_name'],
                        $member['member_name'],
                        $member['membership_number'],
                    ];
                    foreach ($data['schemes'] as $scheme) {
                        $row[] = number_format($member['schemes'][$scheme['id']] ?? 0, 2, '.', '');
                    }
                    $row[] = number_format($member['total'], 2, '.', '');
                    fputcsv($out, $row);
                }

                // Branch total
                $branchTotalRow = [
                    $branch['branch_name'] . ' TOTAL',
                    '',
                    '',
                ];
                foreach ($data['schemes'] as $scheme) {
                    $branchTotalRow[] = number_format($branch['totals'][$scheme['id']] ?? 0, 2, '.', '');
                }
                $branchTotalRow[] = number_format($branch['branch_total'], 2, '.', '');
                fputcsv($out, $branchTotalRow);

                fputcsv($out, []); // Empty line between branches
            }

            // Grand total
            if (count($data['branches']) > 1) {
                $grandTotalRow = [
                    'GRAND TOTAL',
                    $data['grand_total_members_count'] . ' members',
                    '',
                ];
                foreach ($data['schemes'] as $scheme) {
                    $grandTotalRow[] = number_format($data['grand_totals'][$scheme['id']] ?? 0, 2, '.', '');
                }
                $grandTotalRow[] = number_format($data['grand_total_all'], 2, '.', '');
                fputcsv($out, $grandTotalRow);
            }

            fclose($out);
        }, 'scheme-branch-report.csv', $headers);
    }
}
