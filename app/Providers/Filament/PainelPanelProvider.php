<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PainelPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('painel')
            ->path('painel')
            ->login(\App\Filament\Painel\Pages\Login::class)
            ->brandName('PadelMatch - Jogador')
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::hex('#F07B30'),
            ])
            ->darkMode(false)
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString('
                    <style>
                        .fi-panel-painel .fi-simple-layout {
                            background-color: #1B6B7A;
                        }
                        .fi-panel-painel .fi-simple-main {
                            box-shadow: 0 4px 32px rgba(0,0,0,0.25);
                        }
                        .fi-panel-painel .fi-sidebar-header {
                            background-color: #1B6B7A !important;
                            border-bottom: 1px solid rgba(255,255,255,0.15);
                        }
                        .fi-panel-painel .fi-sidebar {
                            background-color: #154f5c !important;
                        }
                        .fi-panel-painel .fi-sidebar-nav {
                            background-color: #154f5c !important;
                        }
                        .fi-panel-painel .fi-sidebar-header .fi-logo img {
                            filter: brightness(0) invert(1);
                        }
                    </style>
                '),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): HtmlString => new HtmlString(
                    '<div class="text-center mt-2 flex flex-col gap-1">
                        <a href="/gerente/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                            Acessar como Gerente
                        </a>
                        <a href="/admin/login" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                            Acessar como Administrador
                        </a>
                    </div>'
                ),
            )
            ->discoverResources(in: app_path('Filament/Painel/Resources'), for: 'App\\Filament\\Painel\\Resources')
            ->discoverPages(in: app_path('Filament/Painel/Pages'), for: 'App\\Filament\\Painel\\Pages')
            ->discoverWidgets(in: app_path('Filament/Painel/Widgets'), for: 'App\\Filament\\Painel\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
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
