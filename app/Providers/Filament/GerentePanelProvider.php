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

class GerentePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('gerente')
            ->path('gerente')
            ->login(\App\Filament\Gerente\Pages\Login::class)
            ->brandName('PadelMatch - Gerente')
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
                        .fi-panel-gerente .fi-simple-layout {
                            background-color: #1B6B7A;
                        }
                        .fi-panel-gerente .fi-simple-main {
                            box-shadow: 0 4px 32px rgba(0,0,0,0.25);
                        }
                        .fi-panel-gerente .fi-sidebar-header {
                            background-color: #1B6B7A !important;
                            border-bottom: 1px solid rgba(255,255,255,0.15);
                        }
                        .fi-panel-gerente .fi-sidebar {
                            background-color: #154f5c !important;
                        }
                        .fi-panel-gerente .fi-sidebar-nav {
                            background-color: #154f5c !important;
                        }
                        .fi-panel-gerente .fi-sidebar-header .fi-logo img {
                            filter: brightness(0) invert(1);
                        }
                        .pm-login-divider {
                            display: flex;
                            align-items: center;
                            gap: 0.75rem;
                            margin: 1.5rem 0 1rem;
                        }
                        .pm-login-divider::before,
                        .pm-login-divider::after {
                            content: "";
                            flex: 1;
                            height: 1px;
                            background: #e5e7eb;
                        }
                        .pm-login-divider span {
                            font-size: 0.7rem;
                            color: #9ca3af;
                            text-transform: uppercase;
                            letter-spacing: 0.06em;
                            white-space: nowrap;
                        }
                        .pm-login-access-links {
                            display: flex;
                            flex-direction: column;
                            gap: 0.5rem;
                        }
                        .pm-login-access-btn {
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 0.5rem;
                            padding: 0.5rem 1rem;
                            border-radius: 0.5rem;
                            border: 1.5px solid #e5e7eb;
                            font-size: 0.875rem;
                            font-weight: 500;
                            color: #374151;
                            text-decoration: none;
                            transition: border-color 0.2s, color 0.2s, background 0.2s;
                            background: #ffffff;
                        }
                        .pm-login-access-btn:hover {
                            border-color: #F07B30;
                            color: #F07B30;
                            background: #fff8f4;
                        }
                    </style>
                '),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): HtmlString => new HtmlString('
                    <div class="pm-login-divider"><span>ou acesse como</span></div>
                    <div class="pm-login-access-links">
                        <a href="/admin/login" class="pm-login-access-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                            Administrador
                        </a>
                        <a href="/painel/login" class="pm-login-access-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                            Jogador
                        </a>
                    </div>
                '),
            )
            ->discoverResources(in: app_path('Filament/Gerente/Resources'), for: 'App\\Filament\\Gerente\\Resources')
            ->discoverPages(in: app_path('Filament/Gerente/Pages'), for: 'App\\Filament\\Gerente\\Pages')
            ->discoverWidgets(in: app_path('Filament/Gerente/Widgets'), for: 'App\\Filament\\Gerente\\Widgets')
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
