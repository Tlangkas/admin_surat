<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\RegisterGukar;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * PanelProvider: AdminPanel
 *
 * Mendaftarkan panel Filament dengan ID 'admin' di path '/admin'.
 * Mengusung tema Clean Corporate Emerald dengan pendaftaran mandiri Gukar.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration(RegisterGukar::class)
            ->brandName('E-Surat Sekolah')
            ->colors([
                'primary' => Color::Emerald,
                'gray' => Color::Slate,
            ])
            ->font('Inter', provider: \Filament\FontProviders\LocalFontProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('7xl')
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Layanan Surat'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Master Data'),
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Pengaturan & Audit'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
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
            ])
            ->databaseNotifications()
            ->darkMode(true)
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('
                    <style>
                        /* Fast native font stack (instant render without external CDN latency) */
                        body, .fi-body {
                            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
                        }
                        /* Custom Table, Badges & Buttons UI Polish */
                        .fi-ta-table { font-size: 0.925rem; }
                        .fi-ta-cell { padding-top: 0.875rem !important; padding-bottom: 0.875rem !important; }
                        .fi-badge { font-weight: 600; border-radius: 0.375rem; }
                        .fi-btn { border-radius: 0.5rem; transition: all 0.15s ease-in-out; }
                        .fi-btn:hover { transform: translateY(-1px); }
                        /* Responsive Mobile Polish */
                        @media (max-width: 768px) {
                            .fi-ta-content { padding: 0.75rem !important; }
                            .fi-ta-record { margin-bottom: 0.75rem; border-radius: 0.75rem; }
                        }
                    </style>
                ')
            )
            ->homeUrl(fn (): string => Auth::user() && Auth::user()->isGukar()
                ? url('/admin/pengajuan-surat')
                : url('/admin'));
    }
}
