<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\SensorTable;
use App\Filament\Widgets\SensorChart;
use App\Filament\Widgets\KelembabanChart;
use App\Filament\Widgets\LabMonitor;
use App\Filament\Widgets\UserMonitor;
use App\Filament\Widgets\TempHum;
use Filament\Navigation\NavigationItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Dashboard IOT')
            ->favicon(asset('images/favicon.ico'))
            ->navigationItems([
                NavigationItem::make('Data Penggunaan Lab')
                    ->url('https://docs.google.com/spreadsheets/d/1D8eet_-uTWe9GiIdpy9yI1M1R8qRxh_C1NIjlOMZUvw/edit?usp=sharing', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-presentation-chart-line')
                    ->group('Laporan External')
                    ->sort(3),
            ])

            ->sidebarWidth('15rem')
            ->sidebarFullyCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                UserMonitor::class,
                LabMonitor::class,
                TempHum::class,
                SensorChart::class,
                KelembabanChart::class,
                SensorTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
            'panels::head.done',
            fn () => new \Illuminate\Support\HtmlString("
                <style>
                    /* Memberikan efek card yang lebih timbul */
                    .fi-wi-stats-overview-stat {
                        border: 1px solid rgba(var(--primary-500), 0.1) !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
                        transition: transform 0.2s ease-in-out;
                    }
                    /* Efek hover agar interaktif */
                    .fi-wi-stats-overview-stat:hover {
                        transform: scale(1.02);
                        border-color: rgba(var(--primary-500), 0.5) !important;
                    }
                </style>
            ")
        );
    }
}
