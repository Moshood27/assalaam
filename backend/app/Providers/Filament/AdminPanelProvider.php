<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\WhitelistedIpResource;
use App\Filament\Resources\AdministrativeChargeResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Resources\AgmCandidateResource;
use App\Filament\Resources\AgmSessionResource;
use App\Filament\Resources\BranchResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CharityEntryResource;
use App\Filament\Resources\ContributionResource;
use App\Filament\Resources\ExpenseEntryResource;
use App\Filament\Resources\GoalBookingResource;
use App\Filament\Resources\IncomeEntryResource;
use App\Filament\Resources\MemberApplicationResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProjectInvestmentResource;
use App\Filament\Resources\ProjectProfitResource;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\QardHasanResource;
use App\Filament\Resources\SavingsGoalResource;
use App\Filament\Resources\SchemeResource;
use App\Filament\Resources\SadaqahProjectResource;
use App\Filament\Resources\ZakatResource;
use App\Filament\Resources\ShariaBoardMemberResource;
use App\Filament\Resources\ShariaDisputeResource;
use App\Filament\Resources\ShariahAuditLogResource;
use App\Filament\Resources\StoreOrderResource;
use App\Filament\Resources\SupportMessageResource;
use App\Filament\Resources\TakafulContributionResource;
use App\Filament\Resources\TakafulPoolEntryResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UtilityTransactionResource;
use App\Filament\Resources\WalletTransactionResource;
use App\Filament\Resources\WithdrawalRequestResource;
use App\Filament\Widgets\FinanceSnapshot;
use App\Filament\Widgets\MemberGrowthChart;
use App\Filament\Widgets\RecentPayouts;
use App\Filament\Widgets\RecentWalletActivity;
use App\Filament\Widgets\StoreOverview;
use App\Filament\Widgets\SystemHealthChart;
use App\Filament\Widgets\TotalCollectionsToday;
use App\Filament\Widgets\UserGrowthChart;
use App\Http\Middleware\IpWhitelistMiddleware;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->passwordReset()
            ->brandName(config('brand.name'))
            ->brandLogo(asset('images/'.config('brand.slug', 'attaqwa').'-logo.svg'))
            ->brandLogoHeight('3rem')
            ->darkModeBrandLogo(asset('images/'.config('brand.slug', 'attaqwa').'-logo-dark.svg'))
            ->favicon(asset('images/'.config('brand.slug', 'attaqwa').'-favicon.svg'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // Explicitly register key resources to ensure they appear in navigation
            ->resources([
                AgmCandidateResource::class,
                AgmSessionResource::class,
                BranchResource::class,
                ContributionResource::class,
                ExpenseEntryResource::class,
                GoalBookingResource::class,
                IncomeEntryResource::class,
                ProductResource::class,
                CategoryResource::class,
                StoreOrderResource::class,
                ProjectResource::class,
                ProjectInvestmentResource::class,
                ProjectProfitResource::class,
                QardHasanResource::class,
                SavingsGoalResource::class,
                SchemeResource::class,
                SadaqahProjectResource::class,
                ZakatResource::class,
                ShariaBoardMemberResource::class,
                ShariaDisputeResource::class,
                ShariahAuditLogResource::class,
                TakafulContributionResource::class,
                TakafulPoolEntryResource::class,
                UserResource::class,
                WithdrawalRequestResource::class,
                MemberApplicationResource::class,
                CharityEntryResource::class,
                SupportMessageResource::class,
                WalletTransactionResource::class,
                UtilityTransactionResource::class,
                ActivityLogResource::class,
                WhitelistedIpResource::class,
                AdministrativeChargeResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StoreOverview::class,
                FinanceSnapshot::class,
                RecentPayouts::class,
                TotalCollectionsToday::class,
                SystemHealthChart::class,
                UserGrowthChart::class,
                MemberGrowthChart::class,
                RecentWalletActivity::class,
                Widgets\AccountWidget::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: true,
                        slug: 'my-profile'
                    )
                    ->enableTwoFactorAuthentication()
                    ->enableBrowserSessions(),
            ])
            ->renderHook('panels::head.end', fn () => view('filament.print-styles'))
            ->renderHook('panels::body.start', fn () => view('filament.print-header'))
            ->renderHook('panels::body.end', fn () => view('filament.inactivity-handler'))
            ->middleware([
                IpWhitelistMiddleware::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
