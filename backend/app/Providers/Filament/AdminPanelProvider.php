<?php

namespace App\Providers\Filament;

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
            ->profile()
            ->brandName(config('brand.name'))
            ->brandLogo(asset('images/' . config('brand.slug', 'attaqwa') . '-logo.svg'))
            ->brandLogoHeight('3rem')
            ->darkModeBrandLogo(asset('images/' . config('brand.slug', 'attaqwa') . '-logo-dark.svg'))
            ->favicon(asset('images/' . config('brand.slug', 'attaqwa') . '-favicon.svg'))
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // Explicitly register key resources to ensure they appear in navigation
            ->resources([
                \App\Filament\Resources\ProjectResource::class,
                \App\Filament\Resources\ProjectInvestmentResource::class,
                \App\Filament\Resources\ProjectProfitResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\FinanceSnapshot::class,
                \App\Filament\Widgets\RecentPayouts::class,
                \App\Filament\Widgets\TotalCollectionsToday::class,
                \App\Filament\Widgets\SystemHealthChart::class,
                \App\Filament\Widgets\UserGrowthChart::class,
                \App\Filament\Widgets\MemberGrowthChart::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->renderHook('panels::head.end', fn () => view('filament.print-styles'))
            ->renderHook('panels::body.start', fn () => view('filament.print-header'))
            ->middleware([
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
