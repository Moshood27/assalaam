<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersByBranchReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Management Reports';
    protected static ?string $navigationLabel = 'Users by Branch Report';
    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.users-by-branch-report';

    public function getSubheading(): ?string
    {
        return 'System users grouped by branch with contact details and status.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_user');
    }

    public array $report = [
        'branches' => [],
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
        $user = auth()->user();

        $targetBranchId = $user->hasRole('super_admin') ? $this->branchId : $user->branch_id;
        $this->report = $svc->buildUsersByBranchReport($targetBranchId);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="users-by-branch-report.csv"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Branch', 'Name', 'Membership #', 'Email', 'Phone', 'Status', 'Joined At']);

            foreach ($data['branches'] as $branch) {
                foreach ($branch['members'] as $member) {
                    fputcsv($out, [
                        $branch['branch_name'],
                        $member['member_name'],
                        $member['membership_number'],
                        $member['email'],
                        $member['phone'],
                        ucfirst($member['status']),
                        $member['joined_at'],
                    ]);
                }
                fputcsv($out, []);
            }

            fputcsv($out, [
                'TOTAL USERS',
                $data['grand_total_members_count'],
                '',
                '',
                '',
                '',
                '',
            ]);

            fclose($out);
        }, 'users-by-branch-report.csv', $headers);
    }
}
