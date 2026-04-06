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
            ->colors([
                'primary' => Color::Orange,
            ])
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
