<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Services\AccountingReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class OfficialReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Official PDF Reports';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.official-reports';

    public int $year;
    public ?int $projectId = null;

    public function mount(): void
    {
        $this->year = now()->year;
        $this->projectId = \App\Models\Project::orderBy('created_at', 'desc')->first()?->id;
    }

    public function getProjects(): array
    {
        return \App\Models\Project::orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function downloadProjectDistribution()
    {
        if (!$this->projectId) return null;
        return redirect()->route('download-project-distribution', ['projectId' => $this->projectId, 'token' => $this->getAdminToken()]);
    }

    public function getSubheading(): ?string
    {
        return 'Generate and download professional PDF reports for regulatory and Sharia compliance.';
    }

    public function downloadFinancials()
    {
        return redirect()->route('download-financials', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadAppropriation()
    {
        return redirect()->route('download-appropriation', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadCashFlow()
    {
        return redirect()->route('download-cash-flow', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadCharityReport()
    {
        return redirect()->route('download-charity-report', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadShariaAudit()
    {
        return redirect()->route('download-sharia-audit', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadAttendance()
    {
        return redirect()->route('download-attendance-report', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadLoanAging()
    {
        return redirect()->route('download-loan-aging', ['token' => $this->getAdminToken()]);
    }

    public function downloadTakafulReport()
    {
        return redirect()->route('download-takaful-report', ['token' => $this->getAdminToken()]);
    }

    public function downloadGoldReport()
    {
        return redirect()->route('download-gold-report', ['token' => $this->getAdminToken()]);
    }

    public function downloadCoopZakat()
    {
        return redirect()->route('download-coop-zakat-report', ['token' => $this->getAdminToken()]);
    }

    public function downloadMemberZakatPortfolio()
    {
        return redirect()->route('download-zakat-portfolio', ['year' => $this->year, 'token' => $this->getAdminToken()]);
    }

    public function downloadProjectRoi()
    {
        return redirect()->route('download-project-roi', ['token' => $this->getAdminToken()]);
    }

    public function downloadVendorSettlement()
    {
        return redirect()->route('download-vendor-settlement', ['token' => $this->getAdminToken()]);
    }

    public function downloadAuditTrail()
    {
        return redirect()->route('download-audit-trail', ['token' => $this->getAdminToken()]);
    }

    protected function getAdminToken(): string
    {
        // Since we are in a session-authenticated Filament context,
        // but the API routes use Sanctum, we might need a token.
        // For convenience in this controlled admin environment,
        // we can create a temporary token for the current admin user if needed,
        // or the controller can be updated to allow session-based auth for these routes.

        $user = auth()->user();
        if (!$user) return '';

        // Try to get an existing token or create a temporary one
        return $user->createToken('AdminReportToken', ['*'], now()->addMinutes(5))->plainTextToken;
    }
}
